<?php

// autoload_aliases.php @generated by Strauss

function autoloadAliases( $classname ): void {
  switch( $classname ) {
    case 'AxeWP\\GraphQL\\Abstracts\\Type':
      class_alias(\WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\Type::class, \AxeWP\GraphQL\Abstracts\Type::class);
      break;
    case 'AxeWP\\GraphQL\\Abstracts\\FieldsType':
      class_alias(\WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\FieldsType::class, \AxeWP\GraphQL\Abstracts\FieldsType::class);
      break;
    case 'AxeWP\\GraphQL\\Abstracts\\ObjectType':
      class_alias(\WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\ObjectType::class, \AxeWP\GraphQL\Abstracts\ObjectType::class);
      break;
    case 'AxeWP\\GraphQL\\Abstracts\\EnumType':
      class_alias(\WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\EnumType::class, \AxeWP\GraphQL\Abstracts\EnumType::class);
      break;
    case 'AxeWP\\GraphQL\\Abstracts\\ConnectionType':
      class_alias(\WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\ConnectionType::class, \AxeWP\GraphQL\Abstracts\ConnectionType::class);
      break;
    case 'AxeWP\\GraphQL\\Abstracts\\UnionType':
      class_alias(\WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\UnionType::class, \AxeWP\GraphQL\Abstracts\UnionType::class);
      break;
    case 'AxeWP\\GraphQL\\Abstracts\\InterfaceType':
      class_alias(\WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\InterfaceType::class, \AxeWP\GraphQL\Abstracts\InterfaceType::class);
      break;
    case 'AxeWP\\GraphQL\\Abstracts\\MutationType':
      class_alias(\WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\MutationType::class, \AxeWP\GraphQL\Abstracts\MutationType::class);
      break;
    case 'AxeWP\\GraphQL\\Abstracts\\InputType':
      class_alias(\WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Abstracts\InputType::class, \AxeWP\GraphQL\Abstracts\InputType::class);
      break;
    case 'AxeWP\\GraphQL\\Traits\\TypeNameTrait':
      $includeFile = '<?php namespace AxeWP\GraphQL\Traits; trait TypeNameTrait { use \WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Traits\TypeNameTrait };';
      include "data://text/plain;base64," . base64_encode($includeFile);
      break;
    case 'AxeWP\\GraphQL\\Traits\\TypeResolverTrait':
      $includeFile = '<?php namespace AxeWP\GraphQL\Traits; trait TypeResolverTrait { use \WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Traits\TypeResolverTrait };';
      include "data://text/plain;base64," . base64_encode($includeFile);
      break;
    case 'AxeWP\\GraphQL\\Helper\\Helper':
      class_alias(\WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Helper\Helper::class, \AxeWP\GraphQL\Helper\Helper::class);
      break;
    case 'AxeWP\\GraphQL\\Helper\\Compat':
      class_alias(\WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Helper\Compat::class, \AxeWP\GraphQL\Helper\Compat::class);
      break;
    case 'AxeWP\\GraphQL\\Interfaces\\TypeWithFields':
      $includeFile = '<?php namespace AxeWP\GraphQL\Interfaces; interface TypeWithFields extends \WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Interfaces\TypeWithFields {};';
      include "data://text/plain;base64," . base64_encode($includeFile);
      break;
    case 'AxeWP\\GraphQL\\Interfaces\\TypeWithConnections':
      $includeFile = '<?php namespace AxeWP\GraphQL\Interfaces; interface TypeWithConnections extends \WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Interfaces\TypeWithConnections {};';
      include "data://text/plain;base64," . base64_encode($includeFile);
      break;
    case 'AxeWP\\GraphQL\\Interfaces\\GraphQLType':
      $includeFile = '<?php namespace AxeWP\GraphQL\Interfaces; interface GraphQLType extends \WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Interfaces\GraphQLType {};';
      include "data://text/plain;base64," . base64_encode($includeFile);
      break;
    case 'AxeWP\\GraphQL\\Interfaces\\TypeWithInputFields':
      $includeFile = '<?php namespace AxeWP\GraphQL\Interfaces; interface TypeWithInputFields extends \WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Interfaces\TypeWithInputFields {};';
      include "data://text/plain;base64," . base64_encode($includeFile);
      break;
    case 'AxeWP\\GraphQL\\Interfaces\\TypeWithInterfaces':
      $includeFile = '<?php namespace AxeWP\GraphQL\Interfaces; interface TypeWithInterfaces extends \WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Interfaces\TypeWithInterfaces {};';
      include "data://text/plain;base64," . base64_encode($includeFile);
      break;
    case 'AxeWP\\GraphQL\\Interfaces\\Registrable':
      $includeFile = '<?php namespace AxeWP\GraphQL\Interfaces; interface Registrable extends \WPGraphQL\RankMath\Vendor\AxeWP\GraphQL\Interfaces\Registrable {};';
      include "data://text/plain;base64," . base64_encode($includeFile);
      break;
    default:
      // Not in this autoloader.
      break;
  }
}

spl_autoload_register( 'autoloadAliases' );

