<?php

/**
 * Common classes and interfaces in root namespace for ProcessWire 2.x template/module compatibility
 *
 * This enable backwards compatability with many templates or modules coded for ProcessWire 2.x.
 * To enable or disable, set $config->compat2x = true|false; in your /site/config.php
 *
 * ProcessWire 2.x
 * Copyright (C) 2015 by Ryan Cramer
 * Licensed under GNU/GPL v2, see LICENSE.TXT
 *
 * http://processwire.com
 *
 */

/**** COMMON CLASSES ***************************************************************************/

class Breadcrumbs extends \ProcessWire\Breadcrumbs {}
class Breadcrumb extends \ProcessWire\Breadcrumb {}
abstract class Fieldtype extends \ProcessWire\Fieldtype {}
abstract class FieldtypeMulti extends \ProcessWire\FieldtypeMulti {}
class Field extends \ProcessWire\Field {}
class Fieldgroup extends \ProcessWire\Fieldgroup {}
class HookEvent extends \ProcessWire\HookEvent {}
class ImageSizer extends \ProcessWire\ImageSizer {}
abstract class Inputfield extends \ProcessWire\Inputfield {}
class InputfieldWrapper extends \ProcessWire\InputfieldWrapper {}
class ModuleConfig extends \ProcessWire\ModuleConfig {}
class ModuleJS extends \ProcessWire\ModuleJS {}
class NullPage extends \ProcessWire\NullPage {}
class Page extends \ProcessWire\Page {}
class PageArray extends \ProcessWire\PageArray {}
abstract class PageAction extends \ProcessWire\PageAction {}
class Pagefile extends \ProcessWire\Pagefile {}
class Pagefiles extends \ProcessWire\Pagefiles {}
class Pageimage extends \ProcessWire\Pageimage {}
class Pageimages extends \ProcessWire\Pageimages {}
class Process extends \ProcessWire\Process {}
class Role extends \ProcessWire\Role {}
class Selectors extends \ProcessWire\Selectors {}
class Template extends \ProcessWire\Template {}
class TemplateFile extends \ProcessWire\TemplateFile {}
class Textformatter extends \ProcessWire\Textformatter {}
class User extends \ProcessWire\User {}
abstract class Wire extends \ProcessWire\Wire {}
abstract class WireAction extends \ProcessWire\WireAction {}
class WireArray extends \ProcessWire\WireArray {}
class WireData extends \ProcessWire\WireData {}
class WireHttp extends \ProcessWire\WireHttp {}
class WireMail extends \ProcessWire\WireMail {}
class WireUpload extends \ProcessWire\WireUpload {}

/**** COMMON EXCEPTIONS ***************************************************************************/

class WireException extends \ProcessWire\WireException {}
class WirePermissionException extends \ProcessWire\WirePermissionException {}
class Wire404Exception extends \ProcessWire\Wire404Exception {}
class WireDatabaseException extends \ProcessWire\WireDatabaseException {}

/**** COMMON INTERFACES ***************************************************************************/

interface Module extends \ProcessWire\Module {}
interface ConfigurableModule extends \ProcessWire\ConfigurableModule {}
interface InputfieldHasArrayValue extends \ProcessWire\InputfieldHasArrayValue {}
interface FieldtypePageTitleCompatible extends \ProcessWire\FieldtypePageTitleCompatible {}
interface InputfieldPageListSelection extends \ProcessWire\InputfieldPageListSelection {}
interface InputfieldItemList extends \ProcessWire\InputfieldItemList {}
interface WirePageEditor extends \ProcessWire\WirePageEditor {}
interface LanguagesValueInterface extends \ProcessWire\LanguagesValueInterface {}

/**** COMMON MODULE DEPENDENCIES ******************************************************************/

include_once($config->paths->modules . 'Fieldtype/FieldtypeText.module');
class FieldtypeText extends \ProcessWire\FieldtypeText {}

include_once($config->paths->modules . 'Fieldtype/FieldtypeTextarea.module');
class FieldtypeTextarea extends \ProcessWire\FieldtypeTextarea {}

include_once($config->paths->modules . 'Inputfield/InputfieldDatetime/InputfieldDatetime.module');
class InputfieldDatetime extends \ProcessWire\InputfieldDatetime {}

include_once($config->paths->modules . 'Inputfield/InputfieldForm.module');
class InputfieldForm extends \ProcessWire\InputfieldForm {}

