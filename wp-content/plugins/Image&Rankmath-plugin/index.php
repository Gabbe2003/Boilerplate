<?php
/**
 * Plugin Name:     Pixabay Image Connector
 * Description:     Exposes Rank Math SEO fields in the REST API, swaps featured images via Pixabay or fallback, and provides all settings and manual sync under Tools → Pixabay Sync.
 * Version:         1.8.1
 * Author:          You
 */

defined('ABSPATH')||exit;

/* ------------------------------------------------------------------
 * 0. Expose Rank Math meta via REST
 * ---------------------------------------------------------------- */
add_action( 'init', function () {
	$meta = [
		'rank_math_title',
		'rank_math_description',
		'rank_math_focus_keyword',
		'rank_math_canonical_url',
		'rank_math_og_title',
		'rank_math_og_description',
		'rank_math_og_url',
		'rank_math_og_image',
	];
	foreach ( $meta as $m ) {
		register_post_meta( 'post', $m, [
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => fn() => current_user_can( 'edit_posts' ),
		] );
	}
} );

/* ------------------------------------------------------------------
 * 1. Default settings
 * ---------------------------------------------------------------- */
function ypseo_defaults(){
    return [
        'pixabay_key'=>'51035765-dc055a26a149f644ef6904d34',
        'safesearch'=>'true',
        'cache_days'=>1,
        'fallback_url'=>plugin_dir_url(__FILE__).'assets/fallback.jpg',
    ];
}

/* ------------------------------------------------------------------
 * 2. Settings storage & retrieval
 * ---------------------------------------------------------------- */
function ypseo_get($key){
    $opt=get_option('ypseo_'.$key);
    return false===$opt?ypseo_defaults()[$key]:$opt;
}
function ypseo_update($key,$val){
    update_option('ypseo_'.$key,$val);
}

/* ------------------------------------------------------------------
 * 3. Cron scheduling
 * ---------------------------------------------------------------- */
register_activation_hook(__FILE__,function(){
    if(!wp_next_scheduled('ypseo_daily_pixabay_task'))
        wp_schedule_event(time(),'daily','ypseo_daily_pixabay_task');
});
register_deactivation_hook(__FILE__,function(){
    wp_clear_scheduled_hook('ypseo_daily_pixabay_task');
});
add_action('ypseo_daily_pixabay_task','ypseo_replace_featured_images_daily');

/* ------------------------------------------------------------------
 * 4. Helpers: date query & Pixabay API
 * ---------------------------------------------------------------- */
function ypseo_get_posts_between($from,$to){
    return get_posts([
        'post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1,
        'date_query'=>[[
            'after'=>"{$from} 00:00:00",
            'before'=>"{$to} 23:59:59",
            'inclusive'=>true,
        ]]
    ]);
}
function ypseo_pixabay_query($kw){
    $url=add_query_arg([
        'key'=>ypseo_get('pixabay_key'),
        'q'=>rawurlencode($kw),
        'image_type'=>'photo','per_page'=>5,
        'safesearch'=>ypseo_get('safesearch'),
    ],'https://pixabay.com/api/');
    $r=wp_remote_get($url,['timeout'=>15]);
    if(is_wp_error($r))return['error'=>$r->get_error_message()];
    $d=json_decode(wp_remote_retrieve_body($r),true);
    return is_array($d)?$d:['error'=>'Invalid JSON'];
}

/* ------------------------------------------------------------------
 * 5. Image processing (sideload & metadata)
 * ---------------------------------------------------------------- */
function ypseo_set_post_thumbnail_from_url($post,$url,$kw,$old_id=0){
    require_once ABSPATH.'wp-admin/includes/file.php';
    require_once ABSPATH.'wp-admin/includes/media.php';
    require_once ABSPATH.'wp-admin/includes/image.php';

    $fallback=ypseo_get('fallback_url');
    $is_pix=$url!==$fallback;
    $legacy=['title'=>'','caption'=>'','description'=>'','alt'=>''];
    if($old_id){
        $old=get_post($old_id);
        if($old&&'attachment'===$old->post_type){
            $legacy['title']=$old->post_title;
            $legacy['caption']=$old->post_excerpt;
            $legacy['description']=$old->post_content;
            $legacy['alt']=get_post_meta($old_id,'_wp_attachment_image_alt',true);
        }
    }
    if(!$is_pix){foreach($legacy as&$v)if(!$v)$v=$post->post_title;unset($v);}
    if($is_pix)$legacy['caption'].=($legacy['caption']?' ':'').'photo:Pixabay';
    $tmp=download_url($url);if(is_wp_error($tmp))return false;
    $file=['name'=>sanitize_file_name(wp_basename(parse_url($url,PHP_URL_PATH))),'tmp_name'=>$tmp];
    $aid=media_handle_sideload($file,$post->ID,'',[
        'post_title'=>sanitize_text_field($legacy['title']),
        'post_excerpt'=>sanitize_text_field($legacy['caption']),
        'post_content'=>sanitize_text_field($legacy['description']),
    ]);
    if(is_wp_error($aid)){@unlink($tmp);return false;}
    update_post_meta($aid,'_wp_attachment_image_alt',sanitize_text_field($legacy['alt']?:$kw));
    if($old_id)wp_delete_attachment($old_id,true);
    set_post_thumbnail($post->ID,$aid);
    return true;
}

function ypseo_process_posts($posts,&$log,$include_existing=false){
    $ttl=DAY_IN_SECONDS*ypseo_get('cache_days');
    foreach($posts as$post){
        $e=['post_id'=>$post->ID,'title'=>$post->post_title,'status'=>'','url'=>'','error'=>''];
        if(!$include_existing && has_post_thumbnail($post->ID)){
            $e['status']='Skipped';$log[]=$e;continue;
        }
        $raw=trim(get_post_meta($post->ID,'rank_math_focus_keyword',true));
        $kw=$raw?strtolower(preg_split('/\s+/',$raw)[0]):'';
        $img='';$pix=false;
        if($kw){
            $ck='ypseo_pixabay_'.md5($kw);
            $pd=get_transient($ck);
            if(!$pd){$pd=ypseo_pixabay_query($kw);set_transient($ck,$pd,$ttl);}            
            if(!empty($pd['hits'][0]['largeImageURL'])){$img=$pd['hits'][0]['largeImageURL'];$pix=true;}
        }
        if(!$img)$img=ypseo_get('fallback_url');
        $ok=ypseo_set_post_thumbnail_from_url($post,$img,$kw?:$post->post_title,get_post_thumbnail_id($post->ID));
        $e['status']=$ok?($pix?'Pixabay':'Fallback'):'Error';
        $e['url']=$ok?$img:''; $e['error']=$ok?'':'Failed';
        $log[]=$e;
    }
}

/* ------------------------------------------------------------------
 * 6. Cron handler
 * ---------------------------------------------------------------- */
function ypseo_replace_featured_images_daily(){
    $key='ypseo_completed_'.gmdate('Ymd');
    if(get_transient($key))return;
    $y=gmdate('Y-m-d',strtotime('-1 day'));
    $posts=ypseo_get_posts_between($y,$y);
    $log=[];ypseo_process_posts($posts,$log);
    set_transient($key,1,DAY_IN_SECONDS);
}

/* ------------------------------------------------------------------
 * 7. Admin page under Tools
 * ---------------------------------------------------------------- */
add_action('admin_menu',function(){
    add_submenu_page('tools.php','Pixabay Sync','Pixabay Sync','manage_options','ypseo-sync','ypseo_admin_page');
});

function ypseo_admin_page(){
    if(!current_user_can('manage_options'))wp_die('Unauthorized');

    // Handle settings save
    $settings_saved=false;
    $actual_key=ypseo_get('pixabay_key');
    $mask=substr($actual_key,0,4).str_repeat('*',max(0,strlen($actual_key)-8)).substr($actual_key,-4);
    if(isset($_POST['settings_submit'])&&check_admin_referer('ypseo_settings')){
        $input_key=$_POST['pixabay_key']??'';
        if($input_key!==$mask) ypseo_update('pixabay_key',sanitize_text_field($input_key));
        ypseo_update('safesearch',sanitize_text_field($_POST['safesearch']));
        ypseo_update('cache_days',absint($_POST['cache_days']));
        ypseo_update('fallback_url',esc_url_raw($_POST['fallback_url']));
        $settings_saved=true;
        $actual_key=ypseo_get('pixabay_key');
        $mask=substr($actual_key,0,4).str_repeat('*',max(0,strlen($actual_key)-8)).substr($actual_key,-4);
    }

    // Handle sync
    $sync_run=false; $log=[];
    if(isset($_POST['sync_submit'])&&check_admin_referer('ypseo_sync')){
        if(!empty($_POST['ignore_lock'])) delete_transient('ypseo_completed_'.gmdate('Ymd'));
        $inc=!empty($_POST['include_existing']);
        $from=$_POST['from']?:gmdate('Y-m-d',strtotime('-4 days'));
        $to=$_POST['to']?:gmdate('Y-m-d',strtotime('-1 day'));
        $posts=ypseo_get_posts_between($from,$to);
        ypseo_process_posts($posts,$log,$inc);
        $sync_run=true;
    }

    ?>
    <div class="wrap"><h1>Pixabay Image Sync</h1>

    <h2>Settings</h2>
    <?php if($settings_saved) echo '<div class="notice notice-success"><p>Settings saved.</p></div>'; ?>
    <form method="post"><?php wp_nonce_field('ypseo_settings'); ?>
      <table class="form-table">
        <tr><th>Pixabay API Key</th>
            <td><input type="text" name="pixabay_key" value="<?php echo esc_attr($mask); ?>" class="regular-text">
            <p class="description">Showing first/last 4 chars</p></td></tr>
        <tr><th>Safe Search</th>
            <td><select name="safesearch">
              <option value="true" <?php selected(ypseo_get('safesearch'),'true'); ?>>Enabled</option>
              <option value="false" <?php selected(ypseo_get('safesearch'),'false'); ?>>Disabled</option>
            </select></td></tr>
        <tr><th>Cache Duration (days)</th>
            <td><input type="number" name="cache_days" value="<?php echo esc_attr(ypseo_get('cache_days')); ?>" min="0" class="small-text"></td></tr>
        <tr><th>Fallback URL</th>
            <td><input type="url" name="fallback_url" value="<?php echo esc_url(ypseo_get('fallback_url')); ?>" class="regular-text"></td></tr>
      </table>
      <?php submit_button('Save Settings','primary','settings_submit'); ?>
    </form>

    <h2>Manual Sync</h2>
    <?php if($sync_run) echo '<div class="notice notice-success"><p>'.count($log).' post(s) processed.</p></div>'; ?>
    <form method="post"><?php wp_nonce_field('ypseo_sync'); ?>
      <table class="form-table">
        <tr><th>From</th>
            <td><input type="date" name="from" value="<?php echo esc_attr($_POST['from']?:gmdate('Y-m-d',strtotime('-4 days'))); ?>"></td></tr>
        <tr><th>To</th>
            <td><input type="date" name="to" value="<?php echo esc_attr($_POST['to']?:gmdate('Y-m-d',strtotime('-1 day'))); ?>"></td></tr>
        <tr><th>Ignore daily lock</th>
            <td><label><input type="checkbox" name="ignore_lock"> Yes</label></td></tr>
        <tr><th>Include existing thumbnails</th>
            <td><label><input type="checkbox" name="include_existing"> Yes</label></td></tr>
      </table>
      <?php submit_button('Run Sync','primary','sync_submit'); ?>
    </form>

    <?php if($sync_run){ ?>
      <h2>Run Log</h2>
      <table class="widefat striped"><thead><tr>
        <th>ID</th><th>Title</th><th>Status</th><th>URL/Error</th>
      </tr></thead><tbody>
      <?php foreach($log as$row){ ?>
        <tr>
          <td><?php echo esc_html($row['post_id']); ?></td>
          <td><?php echo esc_html($row['title']); ?></td>
          <td><?php echo esc_html($row['status']); ?></td>
          <td><?php echo esc_html($row['url']?:$row['error']); ?></td>
        </tr>
      <?php } ?></tbody></table>
    <?php } ?>

    </div>
    <?php
}