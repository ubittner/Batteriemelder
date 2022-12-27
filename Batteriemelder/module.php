<?php

/**
 * @project       Batteriemelder/Batteriemelder
 * @file          module.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

include_once __DIR__ . '/helper/BATM_autoload.php';

class Batteriemelder extends IPSModule
{
    //Helper
    use BATM_Config;
    use BATM_MonitoredVariables;
    use BATM_Reports;

    //Constants
    private const MODULE_NAME = 'Batteriemelder';
    private const MODULE_PREFIX = 'BATM';
    private const MODULE_VERSION = '3.0-1, 19.10.2022';
    private const NOTIFICATION_MODULE_GUID = '{BDAB70AA-B45D-4CB4-3D65-509CFF0969F9}';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ########## Properties

        //Info
        $this->RegisterPropertyString('Note', '');
        //Functions
        $this->RegisterPropertyBoolean('EnableActive', false);
        $this->RegisterPropertyBoolean('EnableStatus', true);
        $this->RegisterPropertyBoolean('EnableTriggeringDetector', true);
        $this->RegisterPropertyBoolean('EnableBatteryReplacement', true);
        $this->RegisterPropertyBoolean('EnableBatteryList', true);
        //Location designation
        $this->RegisterPropertyString('LocationDesignation', '');
        //Trigger list
        $this->RegisterPropertyString('TriggerList', '[]');
        //Immediate notification
        $this->RegisterPropertyInteger('ImmediateNotification', 0);
        $this->RegisterPropertyString('ImmediateNotificationResetTime', '{"hour":7,"minute":0,"second":0}');
        $this->RegisterPropertyBoolean('UseImmediateNotificationTotalStateLimit', true);
        $this->RegisterPropertyString('ImmediateNotificationTotalStatusAlarm', '[{"Use":false,"Designation":"Alarm","SpacerNotification":"","LabelMessageText":"","MessageText":"⚠️ Batteriestatus Alarm!","UseTimestamp":true,"SpacerWebFrontNotification":"","LabelWebFrontNotification":"","UseWebFrontNotification":false,"WebFrontNotificationTitle":"","WebFrontNotificationIcon":"","WebFrontNotificationDisplayDuration":0,"SpacerWebFrontPushNotification":"","LabelWebFrontPushNotification":"","UseWebFrontPushNotification":false,"WebFrontPushNotificationTitle":"","WebFrontPushNotificationSound":"","WebFrontPushNotificationTargetID":0,"SpacerMail":"","LabelMail":"","UseMailer":false,"Subject":"","SpacerSMS":"","LabelSMS":"","UseSMS":false,"SMSTitle":"","SpacerTelegram":"","LabelTelegram":"","UseTelegram":false,"TelegramTitle":""}]');
        $this->RegisterPropertyString('ImmediateNotificationTotalStatusOK', '[{"Use":false,"Designation":"OK","SpacerNotification":"","LabelMessageText":"","MessageText":"✅ Batteriestatus OK!","UseTimestamp":true,"SpacerWebFrontNotification":"","LabelWebFrontNotification":"","UseWebFrontNotification":false,"WebFrontNotificationTitle":"","WebFrontNotificationIcon":"","WebFrontNotificationDisplayDuration":0,"SpacerWebFrontPushNotification":"","LabelWebFrontPushNotification":"","UseWebFrontPushNotification":false,"WebFrontPushNotificationTitle":"","WebFrontPushNotificationSound":"","WebFrontPushNotificationTargetID":0,"SpacerMail":"","LabelMail":"","UseMailer":false,"Subject":"","SpacerSMS":"","LabelSMS":"","UseSMS":false,"SMSTitle":"","SpacerTelegram":"","LabelTelegram":"","UseTelegram":false,"TelegramTitle":""}]');
        $this->RegisterPropertyBoolean('UseImmediateNotificationDeviceStateLimit', true);
        $this->RegisterPropertyString('ImmediateNotificationDeviceStatusUpdateOverdue', '[{"Use":false,"Designation":"Überfällige Aktualisierung","SpacerNotification":"","LabelMessageText":"","MessageText": "❗️%1$s Aktualisierung überfällig!","UseTimestamp":true,"SpacerWebFrontNotification":"","LabelWebFrontNotification":"","UseWebFrontNotification":false,"WebFrontNotificationTitle":"","WebFrontNotificationIcon":"","WebFrontNotificationDisplayDuration":0,"SpacerWebFrontPushNotification":"","LabelWebFrontPushNotification":"","UseWebFrontPushNotification":false,"WebFrontPushNotificationTitle":"","WebFrontPushNotificationSound":"","WebFrontPushNotificationTargetID":0,"SpacerMail":"","LabelMail":"","UseMailer":false,"Subject":"","SpacerSMS":"","LabelSMS":"","UseSMS":false,"SMSTitle":"","SpacerTelegram":"","LabelTelegram":"","UseTelegram":false,"TelegramTitle":""}]');
        $this->RegisterPropertyString('ImmediateNotificationDeviceStatusLowBattery', '[{"Use":false,"Designation":"Schwache Batterie","SpacerNotification":"","LabelMessageText":"","MessageText": "⚠️ %1$s Batterie schwach!","UseTimestamp":true,"SpacerWebFrontNotification":"","LabelWebFrontNotification":"","UseWebFrontNotification":false,"WebFrontNotificationTitle":"","WebFrontNotificationIcon":"","WebFrontNotificationDisplayDuration":0,"SpacerWebFrontPushNotification":"","LabelWebFrontPushNotification":"","UseWebFrontPushNotification":false,"WebFrontPushNotificationTitle":"","WebFrontPushNotificationSound":"","WebFrontPushNotificationTargetID":0,"SpacerMail":"","LabelMail":"","UseMailer":false,"Subject":"","SpacerSMS":"","LabelSMS":"","UseSMS":false,"SMSTitle":"","SpacerTelegram":"","LabelTelegram":"","UseTelegram":false,"TelegramTitle":""}]');
        $this->RegisterPropertyString('ImmediateNotificationDeviceStatusOK', '[{"Use":false,"Designation":"OK","SpacerNotification":"","LabelMessageText":"","MessageText": "✅ %1$s Batterie OK!","UseTimestamp":true,"SpacerWebFrontNotification":"","LabelWebFrontNotification":"","UseWebFrontNotification":false,"WebFrontNotificationTitle":"","WebFrontNotificationIcon":"","WebFrontNotificationDisplayDuration":0,"SpacerWebFrontPushNotification":"","LabelWebFrontPushNotification":"","UseWebFrontPushNotification":false,"WebFrontPushNotificationTitle":"","WebFrontPushNotificationSound":"","WebFrontPushNotificationTargetID":0,"SpacerMail":"","LabelMail":"","UseMailer":false,"Subject":"","SpacerSMS":"","LabelSMS":"","UseSMS":false,"SMSTitle":"","SpacerTelegram":"","LabelTelegram":"","UseTelegram":false,"TelegramTitle":""}]');
        //Daily notification
        $this->RegisterPropertyInteger('DailyNotification', 0);
        $this->RegisterPropertyBoolean('DailyNotificationMonday', false);
        $this->RegisterPropertyBoolean('DailyNotificationTuesday', false);
        $this->RegisterPropertyBoolean('DailyNotificationWednesday', false);
        $this->RegisterPropertyBoolean('DailyNotificationThursday', false);
        $this->RegisterPropertyBoolean('DailyNotificationFriday', false);
        $this->RegisterPropertyBoolean('DailyNotificationSaturday', false);
        $this->RegisterPropertyBoolean('DailyNotificationSunday', false);
        $this->RegisterPropertyString('DailyNotificationTime', '{"hour":19,"minute":0,"second":0}');
        $this->RegisterPropertyString('DailyNotificationTotalStatusAlarm', '[{"Use":false,"Designation":"Alarm","SpacerNotification":"","LabelMessageText":"","MessageText":"⚠️ Batteriestatus Alarm!","UseTimestamp":true,"SpacerWebFrontNotification":"","LabelWebFrontNotification":"","UseWebFrontNotification":false,"WebFrontNotificationTitle":"","WebFrontNotificationIcon":"","WebFrontNotificationDisplayDuration":0,"SpacerWebFrontPushNotification":"","LabelWebFrontPushNotification":"","UseWebFrontPushNotification":false,"WebFrontPushNotificationTitle":"","WebFrontPushNotificationSound":"","WebFrontPushNotificationTargetID":0,"SpacerMail":"","LabelMail":"","UseMailer":false,"Subject":"","SpacerSMS":"","LabelSMS":"","UseSMS":false,"SMSTitle":"","SpacerTelegram":"","LabelTelegram":"","UseTelegram":false,"TelegramTitle":""}]');
        $this->RegisterPropertyString('DailyNotificationTotalStatusOK', '[{"Use":false,"Designation":"OK","SpacerNotification":"","LabelMessageText":"","MessageText":"✅ Batteriestatus OK!","UseTimestamp":true,"SpacerWebFrontNotification":"","LabelWebFrontNotification":"","UseWebFrontNotification":false,"WebFrontNotificationTitle":"","WebFrontNotificationIcon":"","WebFrontNotificationDisplayDuration":0,"SpacerWebFrontPushNotification":"","LabelWebFrontPushNotification":"","UseWebFrontPushNotification":false,"WebFrontPushNotificationTitle":"","WebFrontPushNotificationSound":"","WebFrontPushNotificationTargetID":0,"SpacerMail":"","LabelMail":"","UseMailer":false,"Subject":"","SpacerSMS":"","LabelSMS":"","UseSMS":false,"SMSTitle":"","SpacerTelegram":"","LabelTelegram":"","UseTelegram":false,"TelegramTitle":""}]');
        $this->RegisterPropertyString('DailyNotificationDeviceStatusUpdateOverdue', '[{"Use":false,"Designation":"Überfällige Aktualisierung","SpacerNotification":"","LabelMessageText":"","MessageText": "❗️%1$s Aktualisierung überfällig!","UseTimestamp":true,"SpacerWebFrontNotification":"","LabelWebFrontNotification":"","UseWebFrontNotification":false,"WebFrontNotificationTitle":"","WebFrontNotificationIcon":"","WebFrontNotificationDisplayDuration":0,"SpacerWebFrontPushNotification":"","LabelWebFrontPushNotification":"","UseWebFrontPushNotification":false,"WebFrontPushNotificationTitle":"","WebFrontPushNotificationSound":"","WebFrontPushNotificationTargetID":0,"SpacerMail":"","LabelMail":"","UseMailer":false,"Subject":"","SpacerSMS":"","LabelSMS":"","UseSMS":false,"SMSTitle":"","SpacerTelegram":"","LabelTelegram":"","UseTelegram":false,"TelegramTitle":""}]');
        $this->RegisterPropertyString('DailyNotificationDeviceStatusLowBattery', '[{"Use":false,"Designation":"Schwache Batterie","SpacerNotification":"","LabelMessageText":"","MessageText": "⚠️ %1$s Batterie schwach!","UseTimestamp":true,"SpacerWebFrontNotification":"","LabelWebFrontNotification":"","UseWebFrontNotification":false,"WebFrontNotificationTitle":"","WebFrontNotificationIcon":"","WebFrontNotificationDisplayDuration":0,"SpacerWebFrontPushNotification":"","LabelWebFrontPushNotification":"","UseWebFrontPushNotification":false,"WebFrontPushNotificationTitle":"","WebFrontPushNotificationSound":"","WebFrontPushNotificationTargetID":0,"SpacerMail":"","LabelMail":"","UseMailer":false,"Subject":"","SpacerSMS":"","LabelSMS":"","UseSMS":false,"SMSTitle":"","SpacerTelegram":"","LabelTelegram":"","UseTelegram":false,"TelegramTitle":""}]');
        $this->RegisterPropertyString('DailyNotificationDeviceStatusOK', '[{"Use":false,"Designation":"OK","SpacerNotification":"","LabelMessageText":"","MessageText": "✅ %1$s Batterie OK!","UseTimestamp":true,"SpacerWebFrontNotification":"","LabelWebFrontNotification":"","UseWebFrontNotification":false,"WebFrontNotificationTitle":"","WebFrontNotificationIcon":"","WebFrontNotificationDisplayDuration":0,"SpacerWebFrontPushNotification":"","LabelWebFrontPushNotification":"","UseWebFrontPushNotification":false,"WebFrontPushNotificationTitle":"","WebFrontPushNotificationSound":"","WebFrontPushNotificationTargetID":0,"SpacerMail":"","LabelMail":"","UseMailer":false,"Subject":"","SpacerSMS":"","LabelSMS":"","UseSMS":false,"SMSTitle":"","SpacerTelegram":"","LabelTelegram":"","UseTelegram":false,"TelegramTitle":""}]');
        //Weekly notification
        $this->RegisterPropertyInteger('WeeklyNotification', 0);
        $this->RegisterPropertyInteger('WeeklyNotificationDay', 0);
        $this->RegisterPropertyString('WeeklyNotificationTime', '{"hour":19,"minute":0,"second":0}');
        $this->RegisterPropertyString('WeeklyNotificationTotalStatusAlarm', '[{"Use":false,"Designation":"Alarm","SpacerNotification":"","LabelMessageText":"","MessageText":"⚠️ Batteriestatus Alarm!","UseTimestamp":true,"SpacerWebFrontNotification":"","LabelWebFrontNotification":"","UseWebFrontNotification":false,"WebFrontNotificationTitle":"","WebFrontNotificationIcon":"","WebFrontNotificationDisplayDuration":0,"SpacerWebFrontPushNotification":"","LabelWebFrontPushNotification":"","UseWebFrontPushNotification":false,"WebFrontPushNotificationTitle":"","WebFrontPushNotificationSound":"","WebFrontPushNotificationTargetID":0,"SpacerMail":"","LabelMail":"","UseMailer":false,"Subject":"","SpacerSMS":"","LabelSMS":"","UseSMS":false,"SMSTitle":"","SpacerTelegram":"","LabelTelegram":"","UseTelegram":false,"TelegramTitle":""}]');
        $this->RegisterPropertyString('WeeklyNotificationTotalStatusOK', '[{"Use":false,"Designation":"OK","SpacerNotification":"","LabelMessageText":"","MessageText":"✅ Batteriestatus OK!","UseTimestamp":true,"SpacerWebFrontNotification":"","LabelWebFrontNotification":"","UseWebFrontNotification":false,"WebFrontNotificationTitle":"","WebFrontNotificationIcon":"","WebFrontNotificationDisplayDuration":0,"SpacerWebFrontPushNotification":"","LabelWebFrontPushNotification":"","UseWebFrontPushNotification":false,"WebFrontPushNotificationTitle":"","WebFrontPushNotificationSound":"","WebFrontPushNotificationTargetID":0,"SpacerMail":"","LabelMail":"","UseMailer":false,"Subject":"","SpacerSMS":"","LabelSMS":"","UseSMS":false,"SMSTitle":"","SpacerTelegram":"","LabelTelegram":"","UseTelegram":false,"TelegramTitle":""}]');
        $this->RegisterPropertyString('WeeklyNotificationDeviceStatusUpdateOverdue', '[{"Use":false,"Designation":"Überfällige Aktualisierung","SpacerNotification":"","LabelMessageText":"","MessageText": "❗️%1$s Aktualisierung überfällig!","UseTimestamp":true,"SpacerWebFrontNotification":"","LabelWebFrontNotification":"","UseWebFrontNotification":false,"WebFrontNotificationTitle":"","WebFrontNotificationIcon":"","WebFrontNotificationDisplayDuration":0,"SpacerWebFrontPushNotification":"","LabelWebFrontPushNotification":"","UseWebFrontPushNotification":false,"WebFrontPushNotificationTitle":"","WebFrontPushNotificationSound":"","WebFrontPushNotificationTargetID":0,"SpacerMail":"","LabelMail":"","UseMailer":false,"Subject":"","SpacerSMS":"","LabelSMS":"","UseSMS":false,"SMSTitle":"","SpacerTelegram":"","LabelTelegram":"","UseTelegram":false,"TelegramTitle":""}]');
        $this->RegisterPropertyString('WeeklyNotificationDeviceStatusLowBattery', '[{"Use":false,"Designation":"Schwache Batterie","SpacerNotification":"","LabelMessageText":"","MessageText": "⚠️ %1$s Batterie schwach!","UseTimestamp":true,"SpacerWebFrontNotification":"","LabelWebFrontNotification":"","UseWebFrontNotification":false,"WebFrontNotificationTitle":"","WebFrontNotificationIcon":"","WebFrontNotificationDisplayDuration":0,"SpacerWebFrontPushNotification":"","LabelWebFrontPushNotification":"","UseWebFrontPushNotification":false,"WebFrontPushNotificationTitle":"","WebFrontPushNotificationSound":"","WebFrontPushNotificationTargetID":0,"SpacerMail":"","LabelMail":"","UseMailer":false,"Subject":"","SpacerSMS":"","LabelSMS":"","UseSMS":false,"SMSTitle":"","SpacerTelegram":"","LabelTelegram":"","UseTelegram":false,"TelegramTitle":""}]');
        $this->RegisterPropertyString('WeeklyNotificationDeviceStatusOK', '[{"Use":false,"Designation":"OK","SpacerNotification":"","LabelMessageText":"","MessageText": "✅ %1$s Batterie OK!","UseTimestamp":true,"SpacerWebFrontNotification":"","LabelWebFrontNotification":"","UseWebFrontNotification":false,"WebFrontNotificationTitle":"","WebFrontNotificationIcon":"","WebFrontNotificationDisplayDuration":0,"SpacerWebFrontPushNotification":"","LabelWebFrontPushNotification":"","UseWebFrontPushNotification":false,"WebFrontPushNotificationTitle":"","WebFrontPushNotificationSound":"","WebFrontPushNotificationTargetID":0,"SpacerMail":"","LabelMail":"","UseMailer":false,"Subject":"","SpacerSMS":"","LabelSMS":"","UseSMS":false,"SMSTitle":"","SpacerTelegram":"","LabelTelegram":"","UseTelegram":false,"TelegramTitle":""}]');

        ########## General profiles

        //Battery boolean
        $profile = self::MODULE_PREFIX . '.Battery.Boolean';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'OK', 'Information', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 1, 'Batterie schwach', 'Battery', 0xFF0000);

        //Battery integer
        $profile = self::MODULE_PREFIX . '.Battery.Integer';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'OK', 'Information', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 1, 'Batterie schwach', 'Battery', 0xFF0000);

        ########## Variables

        //Active
        $id = @$this->GetIDForIdent('Active');
        $this->RegisterVariableBoolean('Active', 'Aktiv', '~Switch', 10);
        $this->EnableAction('Active');
        if (!$id) {
            $this->SetValue('Active', true);
        }

        //Status
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Status';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'OK', 'Battery', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 1, 'Alarm', 'Warning', 0xFF0000);
        $this->RegisterVariableBoolean('Status', 'Status', $profile, 20);

        //Triggering detector
        $id = @$this->GetIDForIdent('TriggeringDetector');
        $this->RegisterVariableString('TriggeringDetector', 'Auslösender Melder', '', 30);
        $this->SetValue('TriggeringDetector', '');
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('TriggeringDetector'), 'Eyes');
        }

        //Battery replacement
        $id = @$this->GetIDForIdent('BatteryReplacement');
        $this->RegisterVariableInteger('BatteryReplacement', 'Batteriewechsel ID', '', 40);
        $this->EnableAction('BatteryReplacement');
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('BatteryReplacement'), 'Gear');
        }

        //Battery list
        $id = @$this->GetIDForIdent('BatteryList');
        $this->RegisterVariableString('BatteryList', 'Batterieliste', 'HTMLBox', 50);
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('BatteryList'), 'Battery');
        }

        ########## Timer

        $this->RegisterTimer('ResetImmediateNotificationLimit', 0, self::MODULE_PREFIX . '_ResetImmediateNotificationLimit(' . $this->InstanceID . ');');
        $this->RegisterTimer('DailyNotification', 0, self::MODULE_PREFIX . '_ExecuteDailyNotification(' . $this->InstanceID . ', true, true);');
        $this->RegisterTimer('WeeklyNotification', 0, self::MODULE_PREFIX . '_ExecuteWeeklyNotification(' . $this->InstanceID . ', true, true);');

        ########## Attributes

        $this->RegisterAttributeBoolean('UseImmediateNotificationTotalStatusAlarm', true);
        $this->RegisterAttributeBoolean('UseImmediateNotificationTotalStatusOK', true);
        $this->RegisterAttributeString('ImmediateNotificationListDeviceStatusUpdateOverdue', '[]');
        $this->RegisterAttributeString('ImmediateNotificationListDeviceStatusLowBattery', '[]');
        $this->RegisterAttributeString('ImmediateNotificationListDeviceStatusNormal', '[]');
        $this->RegisterAttributeString('DailyNotificationListDeviceStatusUpdateOverdue', '[]');
        $this->RegisterAttributeString('DailyNotificationListDeviceStatusLowBattery', '[]');
        $this->RegisterAttributeString('WeeklyNotificationListDeviceStatusUpdateOverdue', '[]');
        $this->RegisterAttributeString('WeeklyNotificationListDeviceStatusLowBattery', '[]');
    }

    public function ApplyChanges()
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        //Never delete this line!
        parent::ApplyChanges();

        //Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        //Delete all references
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }

        //Delete all update messages
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                if ($message == VM_UPDATE) {
                    $this->UnregisterMessage($senderID, VM_UPDATE);
                }
            }
        }

        //Register references and update messages
        $names = [];
        $names[] = ['propertyName' => 'ImmediateNotification', 'useUpdate' => false];
        $names[] = ['propertyName' => 'DailyNotification', 'useUpdate' => false];
        $names[] = ['propertyName' => 'WeeklyNotification', 'useUpdate' => false];

        foreach ($names as $name) {
            $id = $this->ReadPropertyInteger($name['propertyName']);
            if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                $this->RegisterReference($id);
                if ($name['useUpdate']) {
                    $this->RegisterMessage($id, VM_UPDATE);
                }
            }
        }

        $triggerVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($triggerVariables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            //Primary condition
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                            $this->RegisterReference($id);
                            $this->RegisterMessage($id, VM_UPDATE);
                        }
                    }
                }
            }
        }

        $this->SetTimerInterval('ResetImmediateNotificationLimit', $this->GetInterval('ImmediateNotificationResetTime'));
        $this->SetTimerInterval('DailyNotification', $this->GetInterval('DailyNotificationTime'));
        $this->SetTimerInterval('WeeklyNotification', $this->GetInterval('WeeklyNotificationTime'));

        //WebFront options
        IPS_SetHidden($this->GetIDForIdent('Active'), !$this->ReadPropertyBoolean('EnableActive'));
        IPS_SetHidden($this->GetIDForIdent('Status'), !$this->ReadPropertyBoolean('EnableStatus'));
        IPS_SetHidden($this->GetIDForIdent('TriggeringDetector'), !$this->ReadPropertyBoolean('EnableTriggeringDetector'));
        IPS_SetHidden($this->GetIDForIdent('BatteryReplacement'), !$this->ReadPropertyBoolean('EnableBatteryReplacement'));
        IPS_SetHidden($this->GetIDForIdent('BatteryList'), !$this->ReadPropertyBoolean('EnableBatteryList'));

        $this->CheckBatteries();
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();

        //Delete profiles
        $profiles = ['Status'];
        foreach ($profiles as $profile) {
            $profileName = self::MODULE_PREFIX . '.' . $this->InstanceID . '.' . $profile;
            if (@IPS_VariableProfileExists($profileName)) {
                IPS_DeleteVariableProfile($profileName);
            }
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data): void
    {
        $this->SendDebug(__FUNCTION__, $TimeStamp . ', SenderID: ' . $SenderID . ', Message: ' . $Message . ', Data: ' . print_r($Data, true), 0);
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;

            case VM_UPDATE:
                //$Data[0] = actual value
                //$Data[1] = value changed
                //$Data[2] = last value
                //$Data[3] = timestamp actual value
                //$Data[4] = timestamp value changed
                //$Data[5] = timestamp last value
                $this->CheckBatteries();
                break;

        }
    }

    /**
     * Creates a notification instance
     *
     * @return void
     */
    public function CreateNotificationInstance(): void
    {
        $id = IPS_CreateInstance(self::NOTIFICATION_MODULE_GUID);
        if (is_int($id)) {
            IPS_SetName($id, 'Benachrichtigung');
            echo 'Instanz mit der ID ' . $id . ' wurde erfolgreich erstellt!';
        } else {
            echo 'Instanz konnte nicht erstellt werden!';
        }
    }

    /**
     * Resets the notification limit for immediate notification.
     * @return void
     * @throws Exception
     */
    public function ResetImmediateNotificationLimit(): void
    {
        $this->SetTimerInterval('ResetImmediateNotificationLimit', $this->GetInterval('ImmediateNotificationResetTime'));
        $this->ResetImmediateNotificationTotalState();
        $this->ResetImmediateNotificationDeviceState();
    }

    /**
     * Resets the attributes for immediate notification of the total status to the default values.
     *
     * @return void
     * @throws Exception
     */
    public function ResetImmediateNotificationTotalState(): void
    {
        $this->WriteAttributeBoolean('UseImmediateNotificationTotalStatusOK', true);
        $this->WriteAttributeBoolean('UseImmediateNotificationTotalStatusAlarm', true);
    }

    /**
     * Resets the attributes for immediate notification of the device status to the default values.
     *
     * @return void
     * @throws Exception
     */
    public function ResetImmediateNotificationDeviceState(): void
    {
        $this->ResetAttribute('ImmediateNotificationListDeviceStatusLowBattery');
        $this->ResetAttribute('ImmediateNotificationListDeviceStatusUpdateOverdue');
        $this->ResetAttribute('ImmediateNotificationListDeviceStatusNormal');
    }

    /**
     * Resets a attribute.
     *
     * @param string $Name
     * @return void
     * @throws Exception
     */
    public function ResetAttribute(string $Name): void
    {
        $this->WriteAttributeString($Name, '[]');
    }

    #################### Request action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
            case 'Active':
                $this->SetValue($Ident, $Value);
                break;

            case 'BatteryReplacement':
                $variableID = (int) $Value;
                $this->UpdateBatteryReplacement($variableID);
                break;

        }
    }

    #################### Private

    private function KernelReady()
    {
        $this->ApplyChanges();
    }

    private function GetInterval(string $TimerName): int
    {
        $timer = json_decode($this->ReadPropertyString($TimerName));
        $now = time();
        $hour = $timer->hour;
        $minute = $timer->minute;
        $second = $timer->second;
        $definedTime = $hour . ':' . $minute . ':' . $second;
        if (time() >= strtotime($definedTime)) {
            $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j') + 1, (int) date('Y'));
        } else {
            $timestamp = mktime($hour, $minute, $second, (int) date('n'), (int) date('j'), (int) date('Y'));
        }
        return ($timestamp - $now) * 1000;
    }

    private function SetDefault(): void
    {
        $this->SetValue('Status', false);
        $this->SetValue('BatteryList', '');
        $this->ResetImmediateNotificationLimit();
        $this->ResetAttribute('DailyNotificationListDeviceStatusLowBattery');
        $this->ResetAttribute('DailyNotificationListDeviceStatusUpdateOverdue');
        $this->ResetAttribute('WeeklyNotificationListDeviceStatusLowBattery');
        $this->ResetAttribute('WeeklyNotificationListDeviceStatusUpdateOverdue');
    }
}
