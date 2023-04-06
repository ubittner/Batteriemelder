<?php

/**
 * @project       Batteriemelder/Batteriemelder
 * @file          BATM_MonitoredVariables.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

trait BATM_MonitoredVariables
{
    /**
     * Assigns the profile to the monitored variables.
     *
     * @param bool $Override
     * false =  Profile will only be assigned, if the variables have no existing profile
     * true =   Profile will always be assigned to the variables
     *
     * @return void
     * @throws Exception
     */
    public function AssignVariableProfile(bool $Override): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $overrideText = 'Bestehendes Profil behalten';
        if ($Override) {
            $overrideText = 'Bestehendes Profil überschreiben!';
        }
        $this->SendDebug(__FUNCTION__, 'Variablenprofil: ' . $overrideText, 0);
        //Assign profile only for listed variables
        $monitoredVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        if (!empty($monitoredVariables)) {
            foreach ($monitoredVariables as $variable) {
                //Primary condition
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                            if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                                $variableType = @IPS_GetVariable($id)['VariableType'];
                                $profileName = '';
                                switch ($variableType) {
                                    case 0: //Boolean
                                        $profileName = self::MODULE_PREFIX . '.Battery.Boolean';
                                        break;

                                    case 1: //Integer
                                        $profileName = self::MODULE_PREFIX . '.Battery.Integer';
                                        break;

                                }
                                if ($profileName != '') {
                                    //Always assign profile
                                    if ($Override) {
                                        @IPS_SetVariableCustomProfile($id, $profileName);
                                    } //Only assign profile, if variable has no profile
                                    else {
                                        //Check if variable has a profile
                                        $assignedProfile = @IPS_GetVariable($id)['VariableProfile'];
                                        if (empty($assignedProfile)) {
                                            @IPS_SetVariableCustomProfile($id, $profileName);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        echo 'Die Variablenprofile wurden zugewiesen!';
    }

    /**
     * Creates links of monitored variables.
     *
     * @param int $LinkCategory
     * @return void
     * @throws Exception
     */
    public function CreateVariableLinks(int $LinkCategory): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        if ($LinkCategory == 1 || @!IPS_ObjectExists($LinkCategory)) {
            echo 'Abbruch, bitte wählen Sie eine Kategorie aus!';
            return;
        }
        $icon = 'Battery';
        //Get all monitored variables
        $monitoredVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        $targetIDs = [];
        $i = 0;
        foreach ($monitoredVariables as $variable) {
            if ($variable['CheckBattery'] || $variable['CheckUpdate']) {
                //Primary condition
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                            if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                                $targetIDs[$i] = ['name' => $variable['Designation'], 'targetID' => $id];
                                $i++;
                            }
                        }
                    }
                }
            }
        }
        //Sort array alphabetically by device name
        sort($targetIDs);
        //Get all existing links (links have not an ident field, so we use the object info field)
        $existingTargetIDs = [];
        $links = @IPS_GetLinkList();
        if (!empty($links)) {
            $i = 0;
            foreach ($links as $link) {
                $linkInfo = @IPS_GetObject($link)['ObjectInfo'];
                if ($linkInfo == self::MODULE_PREFIX . '.' . $this->InstanceID) {
                    //Get target id
                    $existingTargetID = @IPS_GetLink($link)['TargetID'];
                    $existingTargetIDs[$i] = ['linkID' => $link, 'targetID' => $existingTargetID];
                    $i++;
                }
            }
        }
        //Delete dead links
        $deadLinks = array_diff(array_column($existingTargetIDs, 'targetID'), array_column($targetIDs, 'targetID'));
        if (!empty($deadLinks)) {
            foreach ($deadLinks as $targetID) {
                $position = array_search($targetID, array_column($existingTargetIDs, 'targetID'));
                $linkID = $existingTargetIDs[$position]['linkID'];
                if (@IPS_LinkExists($linkID)) {
                    @IPS_DeleteLink($linkID);
                }
            }
        }
        //Create new links
        $newLinks = array_diff(array_column($targetIDs, 'targetID'), array_column($existingTargetIDs, 'targetID'));
        if (!empty($newLinks)) {
            foreach ($newLinks as $targetID) {
                $linkID = @IPS_CreateLink();
                @IPS_SetParent($linkID, $LinkCategory);
                $position = array_search($targetID, array_column($targetIDs, 'targetID'));
                @IPS_SetPosition($linkID, $position);
                $name = $targetIDs[$position]['name'];
                @IPS_SetName($linkID, $name);
                @IPS_SetLinkTargetID($linkID, $targetID);
                @IPS_SetInfo($linkID, self::MODULE_PREFIX . '.' . $this->InstanceID);
                @IPS_SetIcon($linkID, $icon);
            }
        }
        //Edit existing links
        $existingLinks = array_intersect(array_column($existingTargetIDs, 'targetID'), array_column($targetIDs, 'targetID'));
        if (!empty($existingLinks)) {
            foreach ($existingLinks as $targetID) {
                $position = array_search($targetID, array_column($targetIDs, 'targetID'));
                $targetID = $targetIDs[$position]['targetID'];
                $index = array_search($targetID, array_column($existingTargetIDs, 'targetID'));
                $linkID = $existingTargetIDs[$index]['linkID'];
                @IPS_SetPosition($linkID, $position);
                $name = $targetIDs[$position]['name'];
                @IPS_SetName($linkID, $name);
                @IPS_SetInfo($linkID, self::MODULE_PREFIX . '.' . $this->InstanceID);
                @IPS_SetIcon($linkID, $icon);
            }
        }
        echo 'Die Variablenverknüpfungen wurden erfolgreich erstellt!';
    }

    /**
     * Determines the trigger variables automatically.
     *
     * @param string $ObjectIdents
     * @return void
     * @throws Exception
     */
    public function DetermineTriggerVariables(string $ObjectIdents): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Identifikator: ' . $ObjectIdents, 0);
        //Determine variables first
        $determinedVariables = [];
        foreach (@IPS_GetVariableList() as $variable) {
            if ($ObjectIdents == '') {
                return;
            }
            $objectIdents = str_replace(' ', '', $ObjectIdents);
            $objectIdents = explode(',', $objectIdents);
            foreach ($objectIdents as $objectIdent) {
                $object = @IPS_GetObject($variable);
                if ($object['ObjectIdent'] == $objectIdent) {
                    $name = @IPS_GetName($variable);
                    $address = '';
                    $lastBatteryReplacement = '{"year":0, "month":0, "day":0}';
                    $parent = @IPS_GetParent($variable);
                    if ($parent > 1 && @IPS_ObjectExists($parent)) { //0 = main category, 1 = none
                        $parentObject = @IPS_GetObject($parent);
                        if ($parentObject['ObjectType'] == 1) { //1 = instance
                            $name = strstr(@IPS_GetName($parent), ':', true);
                            if (!$name) {
                                $name = @IPS_GetName($parent);
                            }
                            $address = @IPS_GetProperty($parent, 'Address');
                            if (!$address) {
                                $address = '';
                            }
                        }
                    }
                    $value = true;
                    if (IPS_GetVariable($variable)['VariableType'] == 1) {
                        $value = 1;
                    }
                    $primaryCondition[0] = [
                        'id'        => 0,
                        'parentID'  => 0,
                        'operation' => 0,
                        'rules'     => [
                            'variable' => [
                                '0' => [
                                    'id'         => 0,
                                    'variableID' => $variable,
                                    'comparison' => 0,
                                    'value'      => $value,
                                    'type'       => 0
                                ]
                            ],
                            'date'         => [],
                            'time'         => [],
                            'dayOfTheWeek' => []
                        ]
                    ];
                    $determinedVariables[] = [
                        'Use'                    => true,
                        'Designation'            => $name,
                        'Comment'                => $address,
                        'BatteryType'            => '',
                        'UserDefinedBatteryType' => '',
                        'CheckBattery'           => true,
                        'UseMultipleAlerts'      => false,
                        'PrimaryCondition'       => json_encode($primaryCondition),
                        'CheckUpdate'            => true,
                        'UpdatePeriod'           => 3,
                        'LastBatteryReplacement' => $lastBatteryReplacement];
                }
            }
        }
        //Get already listed variables
        $listedVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($determinedVariables as $determinedVariable) {
            if (array_key_exists('PrimaryCondition', $determinedVariable)) {
                $primaryCondition = json_decode($determinedVariable['PrimaryCondition'], true);
                if ($primaryCondition != '') {
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $determinedVariableID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                            if ($determinedVariableID > 1 && @IPS_ObjectExists($determinedVariableID)) {
                                //Check variable id with already listed variable ids
                                $add = true;
                                foreach ($listedVariables as $listedVariable) {
                                    if (array_key_exists('PrimaryCondition', $listedVariable)) {
                                        $primaryCondition = json_decode($listedVariable['PrimaryCondition'], true);
                                        if ($primaryCondition != '') {
                                            if (array_key_exists(0, $primaryCondition)) {
                                                if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                                                    $listedVariableID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                                                    if ($listedVariableID > 1 && @IPS_ObjectExists($determinedVariableID)) {
                                                        if ($determinedVariableID == $listedVariableID) {
                                                            $add = false;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                //Add new variable to already listed variables
                                if ($add) {
                                    $listedVariables[] = $determinedVariable;
                                }
                            }
                        }
                    }
                }
            }
        }
        //Sort variables by name
        array_multisort(array_column($listedVariables, 'Designation'), SORT_ASC, $listedVariables);
        @IPS_SetProperty($this->InstanceID, 'TriggerList', json_encode(array_values($listedVariables)));
        if (@IPS_HasChanges($this->InstanceID)) {
            @IPS_ApplyChanges($this->InstanceID);
        }
        echo 'Die Auslöser wurden erfolgreich hinzugefügt!';
    }

    /**
     * Updates the battery replacement date.
     *
     * @param int $VariableID
     * @return void
     * @throws Exception
     */
    public function UpdateBatteryReplacement(int $VariableID): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Variable ID: ' . $VariableID, 0);
        $data = [];
        if ($VariableID <= 1 || @!IPS_ObjectExists($VariableID)) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, Die Variable mit der ID ' . $VariableID . 'existiert nicht!', 0);
            return;
        }
        //Check actual status and remove from critical lists if battery status is okay
        foreach (json_decode($this->GetMonitoredVariables(), true) as $monitoredVariable) {
            if ($monitoredVariable['ID'] == $VariableID) {
                if ($monitoredVariable['ActualStatus'] == 0) { //0 = Battery OK
                    //Remove from daily and weekly critical lists
                    $lists = ['DailyNotificationListDeviceStatusUpdateOverdue', 'DailyNotificationListDeviceStatusLowBattery', 'WeeklyNotificationListDeviceStatusUpdateOverdue', 'WeeklyNotificationListDeviceStatusLowBattery'];
                    foreach ($lists as $list) {
                        $variables = json_decode($this->ReadAttributeString($list), true);
                        foreach ($variables as $key => $variable) {
                            if ($variable['ID'] == $VariableID) {
                                unset($variables[$key]);
                            }
                        }
                        $variables = array_values($variables);
                        $this->WriteAttributeString($list, json_encode($variables));
                    }
                }
            }
        }
        //Update trigger list configuration
        $monitoredVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($monitoredVariables as $index => $variable) {
            $id = 0;
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                    }
                }
            }
            if ($id <= 1 || @!IPS_ObjectExists($id)) {
                continue;
            }
            $batteryType = $variable['BatteryType'];
            if ($batteryType == '') {
                $batteryType = $variable['UserDefinedBatteryType'];
            }
            $data[$index]['Use'] = $variable['Use'];
            $data[$index]['Designation'] = $variable['Designation'];
            $data[$index]['Comment'] = $variable['Comment'];
            $data[$index]['BatteryType'] = $batteryType;
            $data[$index]['CheckBattery'] = $variable['CheckBattery'];
            $data[$index]['UseMultipleAlerts'] = $variable['UseMultipleAlerts'];
            $data[$index]['PrimaryCondition'] = $variable['PrimaryCondition'];
            $data[$index]['CheckUpdate'] = $variable['CheckUpdate'];
            $data[$index]['UpdatePeriod'] = $variable['UpdatePeriod'];
            if ($id == $VariableID) {
                $year = date('Y');
                $month = date('n');
                $day = date('j');
                $data[$index]['LastBatteryReplacement'] = '{"year":' . $year . ',"month":' . $month . ',"day":' . $day . '}';
            } else {
                $data[$index]['LastBatteryReplacement'] = $variable['LastBatteryReplacement'];
            }
        }
        IPS_SetProperty($this->InstanceID, 'TriggerList', json_encode($data));
        if (IPS_HasChanges($this->InstanceID)) {
            IPS_ApplyChanges($this->InstanceID);
        }
    }

    /**
     * Checks the batteries.
     *
     * @return bool
     * false =  One or more batteries are not ok
     * true =   All batteries are ok
     *
     * @throws Exception
     */
    public function CheckBatteries(): bool
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $monitoredVariables = json_decode($this->GetMonitoredVariables(), true);
        //Sort variables by name and rebase
        array_multisort(array_column($monitoredVariables, 'Name'), SORT_ASC, $monitoredVariables);
        $monitoredVariables = array_values($monitoredVariables);

        ##### Overall status

        $actualOverallStatus = 0;
        $result = true;
        if (in_array(1, array_column($monitoredVariables, 'ActualStatus')) || in_array(2, array_column($monitoredVariables, 'ActualStatus'))) {
            $actualOverallStatus = 1;
            $result = false;
        }
        $this->SetValue('Status', $actualOverallStatus);

        ##### Triggering detector

        $name = '';
        foreach ($monitoredVariables as $monitoredVariable) {
            if ($monitoredVariable['ActualStatus'] == 2) {
                $name = $monitoredVariable['Name'];
                break;
            }
            if ($monitoredVariable['ActualStatus'] == 1) {
                $name = $monitoredVariable['Name'];
                break;
            }
        }
        $this->SetValue('TriggeringDetector', $name);

        ##### Last Update

        $this->SetValue('LastUpdate', date('d.m.Y H:i:s'));

        ##### Battery list

        $string = '';
        if ($this->ReadPropertyBoolean('EnableBatteryList')) {
            $string = "<table style='width: 100%; border-collapse: collapse;'>";
            $string .= '<tr><td><b>Status</b></td><td><b>Name</b></td><td><b>Bemerkung</b></td><td><b>Batterietyp</b></td><td><b>ID</b></td><td><b>Letzter Batteriewechsel</b></td></tr>';
            if (!empty($monitoredVariables)) {
                $data = 0;
                //Show update overdue first
                if ($this->ReadPropertyBoolean('EnableUpdateOverdue')) {
                    $spacer = false;
                    if (in_array(2, array_column($monitoredVariables, 'ActualStatus'))) {
                        foreach ($monitoredVariables as $monitoredVariable) {
                            $id = $monitoredVariable['ID'];
                            if ($id != 0 && IPS_ObjectExists($id)) {
                                if ($monitoredVariable['ActualStatus'] == 2) {
                                    $string .= '<tr><td>' . $this->ReadPropertyString('UpdateOverdueStatusText') . '</td><td>' . $monitoredVariable['Name'] . '</td><td>' . $monitoredVariable['Comment'] . '</td><td>' . $monitoredVariable['BatteryType'] . '</td><td>' . $id . '</td><td>' . $monitoredVariable['LastBatteryReplacement'] . '</td></tr>';
                                    $data++;
                                    $spacer = true;
                                }
                            }
                        }
                        if ($spacer) {
                            $string .= '<tr><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td></tr>';
                        }
                    }
                }
                //Low battery
                if ($this->ReadPropertyBoolean('EnableLowBattery')) {
                    $spacer = false;
                    if (in_array(1, array_column($monitoredVariables, 'ActualStatus'))) {
                        foreach ($monitoredVariables as $monitoredVariable) {
                            $id = $monitoredVariable['ID'];
                            if ($id != 0 && IPS_ObjectExists($id)) {
                                if ($monitoredVariable['ActualStatus'] == 1) {
                                    $string .= '<tr><td>' . $this->ReadPropertyString('LowBatteryStatusText') . '</td><td>' . $monitoredVariable['Name'] . '</td><td>' . $monitoredVariable['Comment'] . '</td><td>' . $monitoredVariable['BatteryType'] . '</td><td>' . $id . '</td><td>' . $monitoredVariable['LastBatteryReplacement'] . '</td></tr>';
                                    $data++;
                                    $spacer = true;
                                }
                            }
                        }
                        if ($spacer) {
                            $string .= '<tr><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td></tr>';
                        }
                    }
                }
                //Normal status
                if ($this->ReadPropertyBoolean('EnableBatteryOK')) {
                    $spacer = false;
                    if (in_array(0, array_column($monitoredVariables, 'ActualStatus'))) {
                        foreach ($monitoredVariables as $monitoredVariable) {
                            $id = $monitoredVariable['ID'];
                            if ($id != 0 && IPS_ObjectExists($id)) {
                                if ($monitoredVariable['ActualStatus'] == 0) {
                                    $string .= '<tr><td>' . $this->ReadPropertyString('BatteryOKStatusText') . '</td><td>' . $monitoredVariable['Name'] . '</td><td>' . $monitoredVariable['Comment'] . '</td><td>' . $monitoredVariable['BatteryType'] . '</td><td>' . $id . '</td><td>' . $monitoredVariable['LastBatteryReplacement'] . '</td></tr>';
                                    $data++;
                                    $spacer = true;
                                }
                            }
                        }
                        if ($spacer) {
                            $string .= '<tr><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td></tr>';
                        }
                    }
                }
                //Disabled monitoring is last
                if ($this->ReadPropertyBoolean('EnableCheckDisabled')) {
                    $spacer = false;
                    if (in_array(3, array_column($monitoredVariables, 'ActualStatus'))) {
                        foreach ($monitoredVariables as $monitoredVariable) {
                            $id = $monitoredVariable['ID'];
                            if ($id != 0 && IPS_ObjectExists($id)) {
                                if ($monitoredVariable['ActualStatus'] == 3) {
                                    $string .= '<tr><td>' . $this->ReadPropertyString('MonitoringDisabledStatusText') . '</td><td>' . $monitoredVariable['Name'] . '</td><td>' . $monitoredVariable['Comment'] . '</td><td>' . $monitoredVariable['BatteryType'] . '</td><td>' . $id . '</td><td>' . $monitoredVariable['LastBatteryReplacement'] . '</td></tr>';
                                    $data++;
                                    $spacer = true;
                                }
                            }
                        }
                        if ($spacer) {
                            $string .= '<tr><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td></tr>';
                        }
                    }
                }
                //Remove last spacer
                if ($data > 0) {
                    $string = substr($string, 0, strrpos($string, '<tr><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td></tr>'));
                }
            }
            $string .= '</table>';
        }
        $this->SetValue('BatteryList', $string);

        ##### Device status

        foreach ($monitoredVariables as $monitoredVariable) {
            $actualStatus = $monitoredVariable['ActualStatus']; //0 = OK, 1 = low battery, 2 = update overdue, 3 = monitoring disabled

            ### Monitoring disabled

            if ($actualStatus == 3) {
                continue;
            }

            ### Battery OK

            if ($actualStatus == 0) {
                $timeStamp = date('d.m.Y, H:i:s');
                $statusChanged = false;
                //Check if status was low battery or update overdue before
                if (in_array($monitoredVariable['ID'], array_column(json_decode($this->ReadAttributeString('ImmediateNotificationListDeviceStatusUpdateOverdue'), true), 'ID'))) {
                    $statusChanged = true;
                }
                if (in_array($monitoredVariable['ID'], array_column(json_decode($this->ReadAttributeString('ImmediateNotificationListDeviceStatusLowBattery'), true), 'ID'))) {
                    $statusChanged = true;
                }
                if ($statusChanged) {
                    if (!in_array($monitoredVariable['ID'], array_column(json_decode($this->ReadAttributeString('ImmediateNotificationListDeviceStatusNormal'), true), 'ID'))) {
                        //Add to list: battery OK
                        $variables = json_decode($this->ReadAttributeString('ImmediateNotificationListDeviceStatusNormal'), true);
                        if (!in_array($monitoredVariable['ID'], array_column($variables, 'ID'))) {
                            $variables[] = [
                                'ID'        => $monitoredVariable['ID'],
                                'Timestamp' => $timeStamp];
                            $this->WriteAttributeString('ImmediateNotificationListDeviceStatusNormal', json_encode($variables));
                        }
                        //Notify
                        if ($this->GetValue('Active')) {

                            # Immediate Notification: Battery OK

                            foreach (json_decode($this->ReadPropertyString('ImmediateNotification'), true) as $notification) {
                                if (!$notification['Use']) {
                                    continue;
                                }
                                $notificationID = $notification['ID'];
                                if ($notificationID <= 1 || @!IPS_ObjectExists($notificationID)) {
                                    continue;
                                }
                                if (!$notification['UseBatteryOK']) {
                                    continue;
                                }
                                $text = $notification['BatteryOKMessageText'];
                                //Check for placeholder
                                if (strpos($text, '%1$s') !== false) {
                                    $text = sprintf($text, $monitoredVariable['Name']);
                                }
                                if ($notification['UseBatteryOKTimestamp']) {
                                    $text = $text . ' ' . $timeStamp;
                                }
                                $scriptText = 'WFC_SendNotification(' . $notificationID . ', "' . $notification['BatteryOKTitle'] . '", "' . $text . '", "' . $notification['BatteryOKIcon'] . '", ' . $notification['BatteryOKDisplayDuration'] . ');';
                                @IPS_RunScriptText($scriptText);
                                IPS_Sleep(100);
                            }

                            # Immediate push notification: Battery OK

                            foreach (json_decode($this->ReadPropertyString('ImmediatePushNotification'), true) as $pushNotification) {
                                if (!$pushNotification['Use']) {
                                    continue;
                                }
                                $pushNotificationID = $pushNotification['ID'];
                                if ($pushNotificationID <= 1 || @!IPS_ObjectExists($pushNotificationID)) {
                                    continue;
                                }
                                if (!$pushNotification['UseBatteryOK']) {
                                    continue;
                                }
                                //Title length max 32 characters
                                $title = substr($pushNotification['BatteryOKTitle'], 0, 32);
                                $text = "\n" . $pushNotification['BatteryOKMessageText'];
                                //Check for placeholder
                                if (strpos($text, '%1$s') !== false) {
                                    $text = sprintf($text, $monitoredVariable['Name']);
                                }
                                if ($pushNotification['UseBatteryOKTimestamp']) {
                                    $text = $text . ' ' . $timeStamp;
                                }
                                //Text length max 256 characters
                                $text = substr($text, 0, 256);
                                $scriptText = 'WFC_PushNotification(' . $pushNotificationID . ', "' . $title . '", "' . $text . '", "' . $pushNotification['BatteryOKSound'] . '", ' . $pushNotification['BatteryOKTargetID'] . ');';
                                @IPS_RunScriptText($scriptText);
                                IPS_Sleep(100);
                            }

                            # Immediate email notification: Battery OK

                            foreach (json_decode($this->ReadPropertyString('ImmediateMailerNotification'), true) as $mailer) {
                                $mailerID = $mailer['ID'];
                                if ($mailerID <= 1 || @!IPS_ObjectExists($mailerID)) {
                                    continue;
                                }
                                if (!$mailer['Use']) {
                                    continue;
                                }
                                if (!$mailer['UseBatteryOK']) {
                                    continue;
                                }
                                $batteryOKMessageText = "Batterie OK:\n\n";
                                //Message text
                                $lineText = $mailer['BatteryOKMessageText'];
                                $name = $monitoredVariable['Name'] . ' ';
                                if ($monitoredVariable['Comment'] != '') {
                                    $name = $name . $monitoredVariable['Comment'];
                                }
                                //Check for placeholder
                                if (strpos($lineText, '%1$s') !== false) {
                                    $lineText = sprintf($lineText, $name);
                                }
                                //Timestamp
                                if ($mailer['UseBatteryOKTimestamp']) {
                                    $lineText = $lineText . ', ' . $timeStamp;
                                }
                                //Variable ID
                                if ($mailer['UseBatteryOKVariableID']) {
                                    $lineText = $lineText . ', ID: ' . $monitoredVariable['ID'];
                                }
                                //Battery type
                                $batteryType = $monitoredVariable['BatteryType'];
                                if ($mailer['UseBatteryOKBatteryType']) {
                                    $lineText = $lineText . ', Batterietyp: ' . $batteryType;
                                }
                                $batteryOKMessageText .= $lineText . "\n";
                                $scriptText = 'MA_SendMessage(' . $mailerID . ', "' . $mailer['Subject'] . '", "' . $batteryOKMessageText . '");';
                                @IPS_RunScriptText($scriptText);
                            }
                        }
                    }
                }
            }

            ### Low battery

            if ($actualStatus == 1) {
                $timeStamp = date('d.m.Y, H:i:s');
                $statusChanged = false;
                //Add to immediate notification list
                $criticalVariables = json_decode($this->ReadAttributeString('ImmediateNotificationListDeviceStatusLowBattery'), true);
                if (!in_array($monitoredVariable['ID'], array_column($criticalVariables, 'ID'))) {
                    $statusChanged = true;
                    $criticalVariables[] = [
                        'ID'        => $monitoredVariable['ID'],
                        'Timestamp' => $timeStamp];
                    $this->WriteAttributeString('ImmediateNotificationListDeviceStatusLowBattery', json_encode($criticalVariables));
                }
                //Add to daily notification list
                $criticalVariables = json_decode($this->ReadAttributeString('DailyNotificationListDeviceStatusLowBattery'), true);
                if (!in_array($monitoredVariable['ID'], array_column($criticalVariables, 'ID'))) {
                    $criticalVariables[] = [
                        'ID'        => $monitoredVariable['ID'],
                        'Timestamp' => $timeStamp];
                    $this->WriteAttributeString('DailyNotificationListDeviceStatusLowBattery', json_encode($criticalVariables));
                }
                //Add to weekly notification list
                $criticalVariables = json_decode($this->ReadAttributeString('WeeklyNotificationListDeviceStatusLowBattery'), true);
                if (!in_array($monitoredVariable['ID'], array_column($criticalVariables, 'ID'))) {
                    $criticalVariables[] = [
                        'ID'        => $monitoredVariable['ID'],
                        'Timestamp' => $timeStamp];
                    $this->WriteAttributeString('WeeklyNotificationListDeviceStatusLowBattery', json_encode($criticalVariables));
                }

                if ($statusChanged) {
                    //Notify
                    if ($this->GetValue('Active')) {

                        # Immediate Notification: Low battery

                        foreach (json_decode($this->ReadPropertyString('ImmediateNotification'), true) as $notification) {
                            if (!$notification['Use']) {
                                continue;
                            }
                            $notificationID = $notification['ID'];
                            if ($notificationID <= 1 || @!IPS_ObjectExists($notificationID)) {
                                continue;
                            }
                            if (!$notification['UseLowBattery']) {
                                continue;
                            }
                            $text = $notification['LowBatteryMessageText'];
                            //Check for placeholder
                            if (strpos($text, '%1$s') !== false) {
                                $text = sprintf($text, $monitoredVariable['Name']);
                            }
                            if ($notification['UseLowBatteryTimestamp']) {
                                $text = $text . ' ' . $timeStamp;
                            }
                            $scriptText = 'WFC_SendNotification(' . $notificationID . ', "' . $notification['LowBatteryTitle'] . '", "' . $text . '", "' . $notification['LowBatteryIcon'] . '", ' . $notification['LowBatteryDisplayDuration'] . ');';
                            @IPS_RunScriptText($scriptText);
                            IPS_Sleep(100);
                        }

                        # Immediate push notification: Low battery

                        foreach (json_decode($this->ReadPropertyString('ImmediatePushNotification'), true) as $pushNotification) {
                            if (!$pushNotification['Use']) {
                                continue;
                            }
                            $pushNotificationID = $pushNotification['ID'];
                            if ($pushNotificationID <= 1 || @!IPS_ObjectExists($pushNotificationID)) {
                                continue;
                            }
                            if (!$pushNotification['UseLowBattery']) {
                                continue;
                            }
                            //Title length max 32 characters
                            $title = substr($pushNotification['LowBatteryTitle'], 0, 32);
                            $text = "\n" . $pushNotification['LowBatteryMessageText'];
                            //Check for placeholder
                            if (strpos($text, '%1$s') !== false) {
                                $text = sprintf($text, $monitoredVariable['Name']);
                            }
                            if ($pushNotification['UseBatteryOKTimestamp']) {
                                $text = $text . ' ' . $timeStamp;
                            }
                            //Text length max 256 characters
                            $text = substr($text, 0, 256);
                            $scriptText = 'WFC_PushNotification(' . $pushNotificationID . ', "' . $title . '", "' . $text . '", "' . $pushNotification['LowBatterySound'] . '", ' . $pushNotification['LowBatteryTargetID'] . ');';
                            @IPS_RunScriptText($scriptText);
                            IPS_Sleep(100);
                        }

                        # Immediate email notification: Low battery

                        foreach (json_decode($this->ReadPropertyString('ImmediateMailerNotification'), true) as $mailer) {
                            $mailerID = $mailer['ID'];
                            if ($mailerID <= 1 || @!IPS_ObjectExists($mailerID)) {
                                continue;
                            }
                            if (!$mailer['Use']) {
                                continue;
                            }
                            if (!$mailer['UseLowBattery']) {
                                continue;
                            }
                            $lowBatteryMessageText = "Batterie schwach:\n\n";
                            //Message text
                            $lineText = $mailer['LowBatteryMessageText'];
                            $name = $monitoredVariable['Name'] . ' ';
                            if ($monitoredVariable['Comment'] != '') {
                                $name = $name . $monitoredVariable['Comment'];
                            }
                            //Check for placeholder
                            if (strpos($lineText, '%1$s') !== false) {
                                $lineText = sprintf($lineText, $name);
                            }
                            //Timestamp
                            if ($mailer['UseLowBatteryTimestamp']) {
                                $lineText = $lineText . ', ' . $timeStamp;
                            }
                            //Variable ID
                            if ($mailer['UseLowBatteryVariableID']) {
                                $lineText = $lineText . ', ID: ' . $monitoredVariable['ID'];
                            }
                            //Battery type
                            $batteryType = $monitoredVariable['BatteryType'];
                            if ($mailer['UseLowBatteryBatteryType']) {
                                $lineText = $lineText . ', Batterietyp: ' . $batteryType;
                            }
                            $lowBatteryMessageText .= $lineText . "\n";
                            $scriptText = 'MA_SendMessage(' . $mailerID . ', "' . $mailer['Subject'] . '", "' . $lowBatteryMessageText . '");';
                            @IPS_RunScriptText($scriptText);
                        }
                    }
                }
            }

            ### Update overdue

            if ($actualStatus == 2) {
                $timeStamp = date('d.m.Y, H:i:s');
                $statusChanged = false;
                //Add to immediate notification list
                $variables = json_decode($this->ReadAttributeString('ImmediateNotificationListDeviceStatusUpdateOverdue'), true);
                if (!in_array($monitoredVariable['ID'], array_column($variables, 'ID'))) {
                    $statusChanged = true;
                    $variables[] = [
                        'ID'        => $monitoredVariable['ID'],
                        'Timestamp' => $timeStamp];
                    $this->WriteAttributeString('ImmediateNotificationListDeviceStatusUpdateOverdue', json_encode($variables));
                }
                //Add to daily notification list
                $variables = json_decode($this->ReadAttributeString('DailyNotificationListDeviceStatusUpdateOverdue'), true);
                if (!in_array($monitoredVariable['ID'], array_column($variables, 'ID'))) {
                    $variables[] = [
                        'ID'        => $monitoredVariable['ID'],
                        'Timestamp' => $timeStamp];
                    $this->WriteAttributeString('DailyNotificationListDeviceStatusUpdateOverdue', json_encode($variables));
                }
                //Add to weekly notification list
                $variables = json_decode($this->ReadAttributeString('WeeklyNotificationListDeviceStatusUpdateOverdue'), true);
                if (!in_array($monitoredVariable['ID'], array_column($variables, 'ID'))) {
                    $variables[] = [
                        'ID'        => $monitoredVariable['ID'],
                        'Timestamp' => $timeStamp];
                    $this->WriteAttributeString('WeeklyNotificationListDeviceStatusUpdateOverdue', json_encode($variables));
                }
                if ($statusChanged) {
                    //Notify
                    if ($this->GetValue('Active')) {

                        # Immediate Notification: Update overdue

                        foreach (json_decode($this->ReadPropertyString('ImmediateNotification'), true) as $notification) {
                            if (!$notification['Use']) {
                                continue;
                            }
                            $notificationID = $notification['ID'];
                            if ($notificationID <= 1 || @!IPS_ObjectExists($notificationID)) {
                                continue;
                            }
                            if (!$notification['UseUpdateOverdue']) {
                                continue;
                            }
                            $text = $notification['UpdateOverdueMessageText'];
                            //Check for placeholder
                            if (strpos($text, '%1$s') !== false) {
                                $text = sprintf($text, $monitoredVariable['Name']);
                            }
                            if ($notification['UseUpdateOverdueTimestamp']) {
                                $text = $text . ' ' . $timeStamp;
                            }
                            $scriptText = 'WFC_SendNotification(' . $notificationID . ', "' . $notification['UpdateOverdueTitle'] . '", "' . $text . '", "' . $notification['UpdateOverdueIcon'] . '", ' . $notification['UpdateOverdueDisplayDuration'] . ');';
                            @IPS_RunScriptText($scriptText);
                            IPS_Sleep(100);
                        }

                        # Immediate push notification: Update overdue

                        foreach (json_decode($this->ReadPropertyString('ImmediatePushNotification'), true) as $pushNotification) {
                            if (!$pushNotification['Use']) {
                                continue;
                            }
                            $pushNotificationID = $pushNotification['ID'];
                            if ($pushNotificationID <= 1 || @!IPS_ObjectExists($pushNotificationID)) {
                                continue;
                            }
                            if (!$pushNotification['UseUpdateOverdue']) {
                                continue;
                            }
                            //Title length max 32 characters
                            $title = substr($pushNotification['UpdateOverdueTitle'], 0, 32);
                            $text = "\n" . $pushNotification['UpdateOverdueMessageText'];
                            //Check for placeholder
                            if (strpos($text, '%1$s') !== false) {
                                $text = sprintf($text, $monitoredVariable['Name']);
                            }
                            if ($pushNotification['UseUpdateOverdueTimestamp']) {
                                $text = $text . ' ' . $timeStamp;
                            }
                            //Text length max 256 characters
                            $text = substr($text, 0, 256);
                            $scriptText = 'WFC_PushNotification(' . $pushNotificationID . ', "' . $title . '", "' . $text . '", "' . $pushNotification['UpdateOverdueSound'] . '", ' . $pushNotification['UpdateOverdueTargetID'] . ');';
                            @IPS_RunScriptText($scriptText);
                            IPS_Sleep(100);
                        }

                        # Immediate email notification: Update overdue

                        foreach (json_decode($this->ReadPropertyString('ImmediateMailerNotification'), true) as $mailer) {
                            $mailerID = $mailer['ID'];
                            if ($mailerID <= 1 || @!IPS_ObjectExists($mailerID)) {
                                continue;
                            }
                            if (!$mailer['Use']) {
                                continue;
                            }
                            if (!$mailer['UseUpdateOverdue']) {
                                continue;
                            }
                            $updateOverdueMessageText = "Aktualisierung überfällig:\n\n";
                            //Message text
                            $lineText = $mailer['UpdateOverdueMessageText'];
                            $name = $monitoredVariable['Name'] . ' ';
                            if ($monitoredVariable['Comment'] != '') {
                                $name = $name . $monitoredVariable['Comment'];
                            }
                            //Check for placeholder
                            if (strpos($lineText, '%1$s') !== false) {
                                $lineText = sprintf($lineText, $name);
                            }
                            //Timestamp
                            if ($mailer['UseUpdateOverdueTimestamp']) {
                                $lineText = $lineText . ', ' . $timeStamp;
                            }
                            //Variable ID
                            if ($mailer['UseUpdateOverdueVariableID']) {
                                $lineText = $lineText . ', ID: ' . $monitoredVariable['ID'];
                            }
                            //Battery type
                            $batteryType = $monitoredVariable['BatteryType'];
                            if ($mailer['UseUpdateOverdueBatteryType']) {
                                $lineText = $lineText . ', Batterietyp: ' . $batteryType;
                            }
                            $updateOverdueMessageText .= $lineText . "\n";
                            $scriptText = 'MA_SendMessage(' . $mailerID . ', "' . $mailer['Subject'] . '", "' . $updateOverdueMessageText . '");';
                            @IPS_RunScriptText($scriptText);
                        }
                    }
                }
            }
        }
        return $result;
    }

    #################### Private

    /**
     * Gets the monitored variables and their status.
     *
     * @return string
     * @throws Exception
     */
    private function GetMonitoredVariables(): string
    {
        $result = [];
        foreach (json_decode($this->ReadPropertyString('TriggerList'), true) as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            //Get variable id
            $id = 0;
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        if ($id <= 1 || @!IPS_ObjectExists($id)) { //0 = main category, 1 = none
                            continue;
                        }
                    }
                }
            }
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $actualStatus = 0;
                //Check low battery
                if ($variable['CheckBattery']) {
                    if (IPS_IsConditionPassing($variable['PrimaryCondition'])) {
                        $actualStatus = 1;
                    }
                }
                //Check update overdue
                if ($variable['CheckUpdate']) {
                    $now = time();
                    $variableUpdate = IPS_GetVariable($id)['VariableUpdated'];
                    $dateDifference = ($now - $variableUpdate) / (60 * 60 * 24);
                    if ($dateDifference > $variable['UpdatePeriod']) {
                        $actualStatus = 2;
                    }
                }
                //Check monitoring disabled
                if (!$variable['CheckBattery'] && !$variable['CheckUpdate']) {
                    $actualStatus = 3;
                }
                //Last battery replacement
                $lastBatteryReplacement = 'Nie';
                $replacementDate = json_decode($variable['LastBatteryReplacement']);
                $lastBatteryReplacementYear = $replacementDate->year;
                $lastBatteryReplacementMonth = $replacementDate->month;
                $lastBatteryReplacementDay = $replacementDate->day;
                if ($lastBatteryReplacementYear != 0 && $lastBatteryReplacementMonth != 0 && $lastBatteryReplacementDay != 0) {
                    $lastBatteryReplacement = sprintf('%02d', $lastBatteryReplacementDay) . '.' . sprintf('%02d', $lastBatteryReplacementMonth) . '.' . $lastBatteryReplacementYear;
                }
                $batteryType = $variable['BatteryType'];
                if ($batteryType == '') {
                    $batteryType = $variable['UserDefinedBatteryType'];
                }
                $result[] = [
                    'ActualStatus'           => $actualStatus, //0 = OK, 1 = low battery, 2 = update overdue, 3 = checks are disabled
                    'Name'                   => $variable['Designation'],
                    'Comment'                => $variable['Comment'],
                    'BatteryType'            => $batteryType,
                    'ID'                     => $id,
                    'LastBatteryReplacement' => $lastBatteryReplacement];
            }
        }
        return json_encode($result);
    }
}