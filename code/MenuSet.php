<?php

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * Class MenuSet
 */
class MenuSet extends DataObject implements PermissionProvider
{
    /**
     * @var array
     */
    private static $db = array(
        'Name' => 'Varchar(255)'
    );

    /**
     * @var array
     */
    private static $has_many = array(
        'MenuItems' => 'MenuItem'
    );

    /**
     * @var array
     */
    private static $searchable_fields = array(
        'Name'
    );

    /**
     * @var array
     */
    private static $summary_fields = array(
        'Name'
    );

    /**
     * @return array
     */
    public function providePermissions()
    {
        return array(
            'MANAGE_MENU_SETS' => 'Manage Menu Sets',
        );
    }

    /**
     * @param mixed $member
     * @return boolean
     */
    public function canCreate($member = null, $context = [])
    {
        return Permission::check('MANAGE_MENU_SETS');
    }

    /**
     * @param mixed $member
     * @return boolean
     */
    public function canDelete($member = null, $context = [])
    {
        return !$this->isDefaultSet() && Permission::check('MANAGE_MENU_SETS');
    }

    /**
     * @param mixed $member
     * @return boolean
     */
    public function canEdit($member = null, $context = [])
    {
        return Permission::check('MANAGE_MENU_SETS');
    }

    /**
     * @param mixed $member
     * @return boolean
     */
    public function canView($member = null, $context = [])
    {
        return Permission::check('MANAGE_MENU_SETS');
    }

    /**
     * @return mixed
     */
    public function Children()
    {
        return $this->MenuItems();
    }

    /**
     * Check if this menu set appears in the default sets config
     * @return bool
     */
    public function isDefaultSet()
    {
        return in_array($this->Name, $this->getDefaultSetNames());
    }

    /**
     * Set up default records based on the yaml config
     */
    public function requireDefaultRecords()
    {
        parent::requireDefaultRecords();

        foreach ($this->getDefaultSetNames() as $name) {
            $existingRecord = MenuSet::get()->filter('Name', $name)->first();

            if (!$existingRecord) {
                $set = new MenuSet();
                $set->Name = $name;
                $set->write();

                DB::alteration_message("MenuSet '$name' created", 'created');
            }
        }
    }

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = new FieldList();

        if ($this->ID != null) {
            $fields->removeByName('Name');

            $fields->push(
                $menuItems = new GridField(
                    'MenuItems',
                    'Menu Items',
                    $this->MenuItems(),
                    $config = GridFieldConfig_RelationEditor::create()
                )
            );

            $config->addComponent(new GridFieldOrderableRows());

        } else {
            $fields->push(new TextField('Name', 'Name (this field can\'t be changed once set)'));
        }

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    public function onBeforeDelete()
    {
        $menuItems = $this->MenuItems();

        if ($menuItems instanceof DataList && count($menuItems) > 0) {
            foreach ($menuItems as $menuItem) {
                $menuItem->delete();
            }
        }

        parent::onBeforeDelete();
    }

    /**
     * Get the MenuSet names configured under MenuSet.default_sets
     *
     * @return string[]
     */
    protected function getDefaultSetNames()
    {
        return $this->config()->get('default_sets') ?: array();
    }
}
