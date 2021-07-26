<?php

namespace NSWDPC\Authentication;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FormAction;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;

class PendingProfile_ItemRequest extends GridFieldDetailForm_ItemRequest {

    private static $allowed_actions = array(
        'edit',
        'view',
        'ItemEditForm',
        'doApprove',
        'doUnapprove',
        'doNotifyApprovers'
    );

    public function ItemEditForm()
    {
        $form = parent::ItemEditForm();
        if ($this->record instanceof PendingProfile) {
            $actions = $form->Actions();
            if ($cms_actions = $this->record->getCMSActions()) {
                foreach ($cms_actions as $action) {
                    $actions->push($action);
                }
            }
        }
        return $form;
    }

    /**
     * Carry out approval action
     */
    public function doApprove($data, $form)
    {
        if ($this->record instanceof PendingProfile) {

            if(!$this->record->canEdit()) {
                $form->sessionMessage('You cannot approve this profile', 'bad');
                return $this->edit(Controller::curr()->getRequest());
            }

            if($this->record->RequireAdminApproval == 0) {
                $form->sessionMessage('This profile does not require approval', 'good');
                return $this->edit(Controller::curr()->getRequest());
            }

            if($this->record->IsAdminApproved == 1) {
                $form->sessionMessage('This profile is already approved', 'good');
                return $this->edit(Controller::curr()->getRequest());
            }

            $this->record->IsAdminApproved = 1;
            $this->record->write();

            $notifier = Injector::inst()->create(Notifier::class);
            $notifier->sendProfileApproved($this->record);
            $form->sessionMessage('Profile approved and notified', 'good');

        }
        return $this->edit(Controller::curr()->getRequest());
    }

    /**
     * Carry out approval action
     */
    public function doUnapprove($data, $form)
    {
        if ($this->record instanceof PendingProfile) {

            if(!$this->record->canEdit()) {
                $form->sessionMessage('You cannot edit this profile', 'bad');
                return $this->edit(Controller::curr()->getRequest());
            }

            if($this->record->RequireAdminApproval == 0) {
                $form->sessionMessage('This profile does not require approval', 'good');
                return $this->edit(Controller::curr()->getRequest());
            }

            if($this->record->IsAdminApproved == 0) {
                $form->sessionMessage('This profile is already unapproved', 'good');
                return $this->edit(Controller::curr()->getRequest());
            }

            $this->record->IsAdminApproved = 0;
            $this->record->write();

            $form->sessionMessage('The profile was unapproved', 'good');

        }
        return $this->edit(Controller::curr()->getRequest());
    }

    public function doNotifyApprovers($data, $form) {
        $admin = Permission::check('ADMIN');
        if(!$admin) {
            return $this->edit(Controller::curr()->getRequest());
        }

        if($this->record->IsAdminApproved == 1) {
            return $this->edit(Controller::curr()->getRequest());
        }

        $notifier = Injector::inst()->create(Notifier::class);
        $notifications = $notifier->sendAdministrationApprovalRequired($this->record);
        $form->sessionMessage("{$notifications} approver(s) notified", 'good');

    }

    /**
     * Apply actions to the CMS action area
     */
    public function getFormActions()
    {
        $actions = parent::getFormActions();

        if(!$this->record->canEdit()) {
            return $actions;
        }

        if($this->record->RequireAdminApproval == 0) {
            return $actions;
        }

        if($this->record->IsAdminApproved == 1) {
            $action = FormAction::create('doUnapprove', 'Unapprove')
                    ->addExtraClass('btn-outline-primary')
                    ->setUseButtonTag(true);
        } else {
            $action = FormAction::create('doApprove', 'Approve')
                    ->addExtraClass('btn-outline-primary')
                    ->setUseButtonTag(true);
        }

        $actions->fieldByName('MajorActions')->push($action);

        if($this->record->IsAdminApproved == 0) {
            $admin = Permission::check('ADMIN');
            if($admin) {
                $action = FormAction::create('doNotifyApprovers', 'Notify approvers')
                        ->addExtraClass('btn-outline-primary')
                        ->setUseButtonTag(true);
                $actions->fieldByName('MajorActions')->push($action);
            }
        }

        return $actions;
    }

}
