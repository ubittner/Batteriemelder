<?php

/**
 * @project       Batteriemelder/Batteriemelder
 * @file          module.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnusedPrivateMethodInspection */
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
    private const LIBRARY_GUID = '{30910CF9-AC0D-A48F-267D-24CE177C6B8C}';
    private const MODULE_GUID = '{3C878C9D-63E0-767D-494C-35AC950EA76D}';
    private const MODULE_PREFIX = 'BATM';
    private const NOTIFICATION_MODULE_GUID = '{BDAB70AA-B45D-4CB4-3D65-509CFF0969F9}';
    private const WEBFRONT_MODULE_GUID = '{3565B1F2-8F7B-4311-A4B6-1BF1D868F39E}';
    private const MAILER_MODULE_GUID = '{C6CF3C5C-E97B-97AB-ADA2-E834976C6A92}';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ########## Properties

        ##### Info

        $this->RegisterPropertyString('Note', '');

        ##### Functions

        $this->RegisterPropertyBoolean('EnableActive', false);
        $this->RegisterPropertyBoolean('EnableStatus', true);
        $this->RegisterPropertyBoolean('EnableTriggeringDetector', true);
        $this->RegisterPropertyBoolean('EnableBatteryReplacement', true);
        $this->RegisterPropertyBoolean('EnableLastUpdate', true);
        $this->RegisterPropertyBoolean('EnableUpdateStatus', true);
        $this->RegisterPropertyBoolean('EnableBatteryList', true);
        $this->RegisterPropertyBoolean('EnableUpdateOverdue', true);
        $this->RegisterPropertyBoolean('EnableLowBattery', true);
        $this->RegisterPropertyBoolean('EnableBatteryOK', true);
        $this->RegisterPropertyBoolean('EnableCheckDisabled', true);
        $this->RegisterPropertyString('UpdateOverdueStatusText', '❗️  Aktualisierung überfällig');
        $this->RegisterPropertyString('LowBatteryStatusText', '⚠️  Batterie schwach');
        $this->RegisterPropertyString('BatteryOKStatusText', '🟢  Batterie OK');
        $this->RegisterPropertyString('MonitoringDisabledStatusText', '❌  Überwachung deaktiviert');

        ##### Trigger list

        $this->RegisterPropertyString('TriggerList', '[]');

        ##### Immediate notification

        $this->RegisterPropertyString('ImmediateNotificationResetTime', '{"hour":7,"minute":0,"second":0}');
        $this->RegisterPropertyString('ImmediateNotification', '[]');
        $this->RegisterPropertyString('ImmediatePushNotification', '[]');
        $this->RegisterPropertyString('ImmediateMailerNotification', '[]');

        ##### Daily notification

        $this->RegisterPropertyBoolean('DailyNotificationMonday', true);
        $this->RegisterPropertyBoolean('DailyNotificationTuesday', true);
        $this->RegisterPropertyBoolean('DailyNotificationWednesday', true);
        $this->RegisterPropertyBoolean('DailyNotificationThursday', true);
        $this->RegisterPropertyBoolean('DailyNotificationFriday', true);
        $this->RegisterPropertyBoolean('DailyNotificationSaturday', true);
        $this->RegisterPropertyBoolean('DailyNotificationSunday', false);
        $this->RegisterPropertyString('DailyNotificationTime', '{"hour":19,"minute":0,"second":0}');
        $this->RegisterPropertyBoolean('DailyNotificationAlwaysResetCriticalVariables', false);
        $this->RegisterPropertyString('DailyNotification', '[]');
        $this->RegisterPropertyString('DailyPushNotification', '[]');
        $this->RegisterPropertyString('DailyMailerNotification', '[]');

        ##### Weekly notification

        $this->RegisterPropertyInteger('WeeklyNotificationDay', 0);
        $this->RegisterPropertyString('WeeklyNotificationTime', '{"hour":19,"minute":0,"second":0}');
        $this->RegisterPropertyString('WeeklyNotification', '[]');
        $this->RegisterPropertyString('WeeklyPushNotification', '[]');
        $this->RegisterPropertyString('WeeklyMailerNotification', '[]');

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

        ##### Active

        $id = @$this->GetIDForIdent('Active');
        $this->RegisterVariableBoolean('Active', 'Aktiv', '~Switch', 10);
        $this->EnableAction('Active');
        if (!$id) {
            $this->SetValue('Active', true);
        }

        ##### Status

        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.Status';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'OK', 'Battery', 0x00FF00);
        IPS_SetVariableProfileAssociation($profile, 1, 'Alarm', 'Warning', 0xFF0000);
        $this->RegisterVariableBoolean('Status', 'Status', $profile, 20);

        ##### Triggering detector

        $id = @$this->GetIDForIdent('TriggeringDetector');
        $this->RegisterVariableString('TriggeringDetector', 'Auslösender Melder', '', 30);
        $this->SetValue('TriggeringDetector', '');
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('TriggeringDetector'), 'Eyes');
        }

        ##### Battery replacement

        $id = @$this->GetIDForIdent('BatteryReplacement');
        $this->RegisterVariableInteger('BatteryReplacement', 'Batteriewechsel ID', '', 40);
        $this->EnableAction('BatteryReplacement');
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('BatteryReplacement'), 'Gear');
        }

        ##### Last update

        $id = @$this->GetIDForIdent('LastUpdate');
        $this->RegisterVariableString('LastUpdate', 'Letzte Aktualisierung', '', 50);
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('LastUpdate'), 'Clock');
        }

        ##### Update status

        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.UpdateStatus';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 1);
        }
        IPS_SetVariableProfileAssociation($profile, 0, 'Aktualisieren', 'Repeat', -1);
        $this->RegisterVariableInteger('UpdateStatus', 'Aktualisierung', $profile, 60);
        $this->EnableAction('UpdateStatus');

        ##### Battery list

        $id = @$this->GetIDForIdent('BatteryList');
        $this->RegisterVariableString('BatteryList', 'Batterieliste', 'HTMLBox', 70);
        if (!$id) {
            IPS_SetIcon($this->GetIDForIdent('BatteryList'), 'Battery');
        }

        ########## Timer

        $this->RegisterTimer('ResetImmediateNotificationLimit', 0, self::MODULE_PREFIX . '_ResetImmediateNotificationLimit(' . $this->InstanceID . ');');
        $this->RegisterTimer('DailyNotification', 0, self::MODULE_PREFIX . '_ExecuteDailyNotification(' . $this->InstanceID . ', true, true);');
        $this->RegisterTimer('WeeklyNotification', 0, self::MODULE_PREFIX . '_ExecuteWeeklyNotification(' . $this->InstanceID . ', true, true);');

        ########## Attributes

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

        //Register notifications
        $names = ['ImmediateNotification',
            'ImmediatePushNotification',
            'ImmediateMailerNotification',
            'DailyNotification',
            'DailyPushNotification',
            'DailyMailerNotification',
            'WeeklyNotification',
            'WeeklyPushNotification',
            'WeeklyMailerNotification'];
        foreach ($names as $name) {
            foreach (json_decode($this->ReadPropertyString($name), true) as $element) {
                if ($element['Use']) {
                    $id = $element['ID'];
                    if ($id > 1 && @IPS_ObjectExists($id)) {
                        $this->RegisterReference($id);
                    }
                }
            }
        }

        //Register trigger list
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
                        if ($id > 1 && @IPS_ObjectExists($id)) {
                            $this->RegisterReference($id);
                            $this->RegisterMessage($id, VM_UPDATE);
                        }
                    }
                }
            }
        }

        //Timer
        $this->SetTimerInterval('ResetImmediateNotificationLimit', $this->GetInterval('ImmediateNotificationResetTime'));
        $this->SetTimerInterval('DailyNotification', $this->GetInterval('DailyNotificationTime'));
        $this->SetTimerInterval('WeeklyNotification', $this->GetInterval('WeeklyNotificationTime'));

        //WebFront options
        IPS_SetHidden($this->GetIDForIdent('Active'), !$this->ReadPropertyBoolean('EnableActive'));
        IPS_SetHidden($this->GetIDForIdent('Status'), !$this->ReadPropertyBoolean('EnableStatus'));
        IPS_SetHidden($this->GetIDForIdent('TriggeringDetector'), !$this->ReadPropertyBoolean('EnableTriggeringDetector'));
        IPS_SetHidden($this->GetIDForIdent('BatteryReplacement'), !$this->ReadPropertyBoolean('EnableBatteryReplacement'));
        IPS_SetHidden($this->GetIDForIdent('LastUpdate'), !$this->ReadPropertyBoolean('EnableLastUpdate'));
        IPS_SetHidden($this->GetIDForIdent('UpdateStatus'), !$this->ReadPropertyBoolean('EnableUpdateStatus'));
        IPS_SetHidden($this->GetIDForIdent('BatteryList'), !$this->ReadPropertyBoolean('EnableBatteryList'));

        //Update
        $this->CheckBatteries();
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();

        //Delete profiles
        $profiles = ['Status', 'UpdateStatus'];
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
     * Creates an instance.
     *
     * @param string $ModuleName
     * @return void
     */
    public function CreateInstance(string $ModuleName): void
    {
        $this->SendDebug(__FUNCTION__, 'Modul: ' . $ModuleName, 0);
        switch ($ModuleName) {
            case 'WebFront':
            case 'WebFrontPush':
                $guid = self::WEBFRONT_MODULE_GUID;
                break;

            case 'Mailer':
                $guid = self::MAILER_MODULE_GUID;
                break;

            default:
                return;
        }
        $this->SendDebug(__FUNCTION__, 'Guid: ' . $guid, 0);
        $id = @IPS_CreateInstance($guid);
        if (is_int($id)) {
            IPS_SetName($id, 'Mailer');
            $infoText = 'Instanz mit der ID ' . $id . ' wurde erfolgreich erstellt!';
        } else {
            $infoText = 'Instanz konnte nicht erstellt werden!';
        }
        $this->UpdateFormField('InfoMessage', 'visible', true);
        $this->UpdateFormField('InfoMessageLabel', 'caption', $infoText);
    }

    /**
     * Resets the notification limit for immediate notification.
     *
     * @return void
     * @throws Exception
     */
    public function ResetImmediateNotificationLimit(): void
    {
        $this->SetTimerInterval('ResetImmediateNotificationLimit', $this->GetInterval('ImmediateNotificationResetTime'));
        $this->ResetImmediateNotificationDeviceState();
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

    public function ResetNotificationLists(): void
    {
        $this->WriteAttributeString('ImmediateNotificationListDeviceStatusUpdateOverdue', '[]');
        $this->WriteAttributeString('ImmediateNotificationListDeviceStatusLowBattery', '[]');
        $this->WriteAttributeString('ImmediateNotificationListDeviceStatusNormal', '[]');
        $this->WriteAttributeString('DailyNotificationListDeviceStatusUpdateOverdue', '[]');
        $this->WriteAttributeString('DailyNotificationListDeviceStatusLowBattery', '[]');
        $this->WriteAttributeString('WeeklyNotificationListDeviceStatusUpdateOverdue', '[]');
        $this->WriteAttributeString('WeeklyNotificationListDeviceStatusLowBattery', '[]');
    }

    public function UIShowMessage(string $Message): void
    {
        $this->UpdateFormField('InfoMessage', 'visible', true);
        $this->UpdateFormField('InfoMessageLabel', 'caption', $Message);
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

            case 'UpdateStatus':
                $this->CheckBatteries();
                break;

        }
    }

    #################### Private

    private function KernelReady()
    {
        $this->ApplyChanges();
    }

    /**
     * Gets an interval for a timer.
     *
     * @param string $TimerName
     * @return int
     * @throws Exception
     */
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

    /**
     * Set the values to default.
     *
     * @return void
     * @throws Exception
     */
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
