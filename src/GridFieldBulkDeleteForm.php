<?php

namespace Heyday\GridFieldBulkDelete;

use SilverStripe\Forms\GridField\GridField_HTMLProvider;
use SilverStripe\Forms\GridField\GridField_FormAction;
use SilverStripe\Forms\GridField\GridField_DataManipulator;
use SilverStripe\Forms\GridField\GridField_ActionProvider;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\SS_List;

/**
 * Adds a dropdown and a button
 * Allowig users to delete records in the gridfield in bulk
 * with the option to delete only records older than 1,2,3 or 6 months.
 *
 * Also, if available, a queued job will be used if too many record need to be deleted.
 * Both this task and queuedjob loop trhough the record and invoke "delete"
 * so any dependant record may be deleted as well.
 *
 */
class GridFieldBulkDeleteForm implements GridField_HTMLProvider, GridField_ActionProvider
{
    /**
     * @var string
     */
    const STATUS_GOOD = 'good';

    /**
     * @var string
     */
    protected $targetFragment;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var string
     */
    protected $status = self::STATUS_GOOD;

    public function __construct($targetFragment = 'before', $threshold = null)
    {
        $this->targetFragment = $targetFragment;

        if ($threshold && $threshold > 0) {
            $this->use_queued_threshold = $threshold;
        }
    }

    /**
     * Return all HTML fragment of delete form
     * 
     * @param GridField $gridField
     * @return array
     */
    public function getHTMLFragments($gridField)
    {
        $form = $gridField->getForm();
        $records = $this->getFilteredRecordList($gridField);
        if (!$records->exists()) {
            /**
             * If a message exists, but no record
             * it means we have deleted them all
             * so display the message anyway
             */
            if ($this->message) {
                $fragment = sprintf('<p class="message %s">%s</p>', $this->status, $this->message);
                return [$this->targetFragment => $fragment];
            }
            // Otherwise hide the form
            return [];
        }

        $singleton = singleton($gridField->getModelClass());
        if (!$singleton->canDelete()) {
            return [];
        }

        $buttonTitle = sprintf('Delete %s record(s)', $records->Count());

        $button = new GridField_FormAction(
            $gridField,
            'bulkdelete',
            $buttonTitle,
            'bulkdelete',
            null
        );

        $button->setForm($gridField->getForm());
        $button->addExtraClass('bulkdelete_button'); // For JS purpose
        $button->addExtraClass('font-icon-trash btn btn-outline-danger'); // For styling purpose

        // Set message
        if ($this->message) {
            $form->setMessage($this->message, $this->status);
        }

        $template = '<div><table><tr><td style="vertical-align:top;"><div style="margin-left: 7px;">%s</div></td></tr></table></div>';

        $fragment = sprintf($template, $button->Field());

        return [
            $this->targetFragment => $fragment
        ];
    }

    /**
     * export is an action button
     * 
     * @param GridField
     * @return array
     */
    public function getActions($gridField)
    {
        return ['bulkdelete'];
    }

    /**
     * export is an action button
     * 
     * @param string $actionName
     * @param mixed $arguments
     * @param mixed $data
     * 
     * @return array
     */
    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName == 'bulkdelete') {
            return $this->handleBulkDelete($gridField);
        }
    }

    /**
     * Handle the export, for both the action button and the URL
     */
    public function handleBulkDelete(GridField $gridField)
    {
        $records = $this->getFilteredRecordList($gridField);

        $ids = [];
        foreach ($records as $record) {
            $ids[] = $record->ID;
            $record->delete();
        }

        $this->message = sprintf('%s records have been successfully deleted.', count($ids));
        $this->status = self::STATUS_GOOD;

        Controller::curr()->getResponse()->setStatusCode(
            200,
            $this->message
        );

        return;
    }

    /**
     * To get the entire list of records with the potential filters
     * we need to remove the pagination but apply all other filters
     */
    public function getFilteredRecordList(GridField $gridfield): SS_List
    {
        $list = $gridfield->getList();

        foreach ($gridfield->getComponents() as $item) {
            if ($item instanceof GridField_DataManipulator && !$item instanceof GridFieldPaginator) {
                $list = $item->getManipulatedData($gridfield, $list);
            }
        }

        return $list;
    }
}
