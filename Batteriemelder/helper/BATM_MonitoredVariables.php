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
        if (!$this->CheckForExistingVariables()) {
            return;
        }
        if ($VariableID <= 1 || @!IPS_ObjectExists($VariableID)) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, Die Variable mit der ID ' . $VariableID . 'existiert nicht!', 0);
            return;
        }
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
            $data[$index]['Use'] = $variable['Use'];
            $data[$index]['Designation'] = $variable['Designation'];
            $data[$index]['Comment'] = $variable['Comment'];
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
        if (!$this->CheckForExistingVariables()) {
            return false;
        }

        ########## Batteries

        $batteries = json_decode($this->GetMonitoredVariables(), true);

        //Update battery list for WebFront
        $string = '';
        if ($this->ReadPropertyBoolean('EnableBatteryList')) {
            $string = "<table style='width: 100%; border-collapse: collapse;'>";
            $string .= '<tr><td><b>Status</b></td><td><b>ID</b></td><td><b>Name</b></td><td><b>Bemerkung</b></td><td><b>Letzter Batteriewechsel</b></td></tr>';
            //Sort variables by name
            array_multisort(array_column($batteries, 'Name'), SORT_ASC, $batteries);
            //Rebase array
            $batteries = array_values($batteries);
            if (!empty($batteries)) {
                //Show update overdue first
                foreach ($batteries as $battery) {
                    $id = $battery['ID'];
                    if ($id != 0 && IPS_ObjectExists($id)) {
                        if ($battery['ActualStatus'] == 2) {
                            $string .= '<tr><td>' . $battery['Unicode'] . '</td><td>' . $id . '</td><td>' . $battery['Name'] . '</td><td>' . $battery['Comment'] . '</td><td>' . $battery['LastBatteryReplacement'] . '</td></tr>';
                        }
                    }
                }
                //Low battery
                foreach ($batteries as $battery) {
                    $id = $battery['ID'];
                    if ($id != 0 && IPS_ObjectExists($id)) {
                        if ($battery['ActualStatus'] == 1) {
                            $string .= '<tr><td>' . $battery['Unicode'] . '</td><td>' . $id . '</td><td>' . $battery['Name'] . '</td><td>' . $battery['Comment'] . '</td><td>' . $battery['LastBatteryReplacement'] . '</td></tr>';
                        }
                    }
                }
                //Normal status
                foreach ($batteries as $battery) {
                    $id = $battery['ID'];
                    if ($id != 0 && IPS_ObjectExists($id)) {
                        if ($battery['ActualStatus'] == 0) {
                            $string .= '<tr><td>' . $battery['Unicode'] . '</td><td>' . $id . '</td><td>' . $battery['Name'] . '</td><td>' . $battery['Comment'] . '</td><td>' . $battery['LastBatteryReplacement'] . '</td></tr>';
                        }
                    }
                }
                //Disabled monitoring is last
                foreach ($batteries as $battery) {
                    $id = $battery['ID'];
                    if ($id != 0 && IPS_ObjectExists($id)) {
                        if ($battery['ActualStatus'] == 3) {
                            $string .= '<tr><td>' . $battery['Unicode'] . '</td><td>' . $id . '</td><td>' . $battery['Name'] . '</td><td>' . $battery['Comment'] . '</td><td>' . $battery['LastBatteryReplacement'] . '</td></tr>';
                        }
                    }
                }
            }
            $string .= '</table>';
        }
        $this->SetValue('BatteryList', $string);

        ########## Overall status

        $lastOverallStatus = $this->GetValue('Status');
        $actualOverallStatus = 0;
        foreach ($batteries as $battery) {
            //Check for low battery and update overdue
            if ($battery['ActualStatus'] == 1 || ($battery['ActualStatus'] == 2)) {
                $actualOverallStatus = 1;
            }
        }
        $this->SetValue('Status', $actualOverallStatus);
        $result = true;
        if ($actualOverallStatus == 1) {
            $result = false;
        }

        ##### Overall status notification

        $notify = false;
        $notificationName = '';
        $limitationNotification = $this->ReadPropertyBoolean('UseImmediateNotificationTotalStateLimit');

        //Status has changed
        if ($lastOverallStatus != $actualOverallStatus) {
            //Status changed from OK to Alarm
            if ($lastOverallStatus == 0 && $actualOverallStatus == 1) {
                $notify = true;
                $notificationName = 'ImmediateNotificationTotalStatusAlarm';
                //Check limitation
                if ($limitationNotification) {
                    //Check attribute
                    if (!$this->ReadAttributeBoolean('UseImmediateNotificationTotalStatusAlarm')) {
                        $notify = false;
                    }
                    $this->WriteAttributeBoolean('UseImmediateNotificationTotalStatusAlarm', false);
                }
            }

            //Status changed from Alarm to OK again
            if ($lastOverallStatus == 1 && $actualOverallStatus == 0) {
                $notify = true;
                $notificationName = 'ImmediateNotificationTotalStatusOK';
                //Check limitation
                if ($limitationNotification) {
                    //Check attribute
                    if (!$this->ReadAttributeBoolean('UseImmediateNotificationTotalStatusOK')) {
                        $notify = false;
                    }
                    $this->WriteAttributeBoolean('UseImmediateNotificationTotalStatusOK', false);
                }
            }
        }

        if ($notify && $notificationName != '' && $this->GetValue('Active')) {
            $id = $this->ReadPropertyInteger('ImmediateNotification');
            if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                $notification = json_decode($this->ReadPropertyString($notificationName), true);
                if ($notification[0]['Use']) {
                    $messageText = $notification[0]['MessageText'];
                    if ($notification[0]['UseTimestamp']) {
                        $messageText = $messageText . ' ' . date('d.m.Y, H:i:s');
                    }
                    $this->SendDebug(__FUNCTION__, 'Meldungstext: ' . $messageText, 0);
                    //WebFront notification
                    if ($notification[0]['UseWebFrontNotification']) {
                        @BN_SendWebFrontNotification($id, $notification[0]['WebFrontNotificationTitle'], "\n" . $messageText, $notification[0]['WebFrontNotificationIcon'], $notification[0]['WebFrontNotificationDisplayDuration']);
                    }
                    //WebFront push notification
                    if ($notification[0]['UseWebFrontPushNotification']) {
                        @BN_SendWebFrontPushNotification($id, $notification[0]['WebFrontPushNotificationTitle'], "\n" . $messageText, $notification[0]['WebFrontPushNotificationSound'], $notification[0]['WebFrontPushNotificationTargetID']);
                    }
                    //E-Mail
                    if ($notification[0]['UseMailer']) {
                        @BN_SendMailNotification($id, $notification[0]['Subject'], "\n\n" . $messageText);
                    }
                    //SMS
                    if ($notification[0]['UseSMS']) {
                        @BN_SendNexxtMobileSMS($id, $notification[0]['SMSTitle'], "\n\n" . $messageText);
                        @BN_SendSipgateSMS($id, $notification[0]['SMSTitle'], "\n\n" . $messageText);
                    }
                    //Telegram
                    if ($notification[0]['UseTelegram']) {
                        @BN_SendTelegramMessage($id, $notification[0]['TelegramTitle'], "\n\n" . $messageText);
                    }
                }
            }
        }

        ########## Device status

        foreach ($batteries as $battery) {
            $actualStatus = $battery['ActualStatus']; //0 = OK, 1 = low battery, 2 = update overdue, 3 = checks are disabled

            ##### Checks are disabled

            if ($actualStatus == 3) {
                continue;
            }

            $notify = false;
            $notificationName = '';

            #####  OK

            if ($actualStatus == 0) {
                $notificationName = 'ImmediateNotificationDeviceStatusOK';
                //Check limitation
                if ($this->ReadPropertyBoolean('UseImmediateNotificationDeviceStateLimit')) {
                    $statusChanged = false;
                    //Check if status was low battery or update overdue before
                    if (in_array($battery['ID'], array_column(json_decode($this->ReadAttributeString('ImmediateNotificationListDeviceStatusUpdateOverdue'), true), 'ID'))) {
                        $statusChanged = true;
                    }
                    if (in_array($battery['ID'], array_column(json_decode($this->ReadAttributeString('ImmediateNotificationListDeviceStatusLowBattery'), true), 'ID'))) {
                        $statusChanged = true;
                    }
                    if ($statusChanged) {
                        $notify = true;
                        //Add to list
                        $attributeName = 'ImmediateNotificationListDeviceStatusNormal';
                        $variables = json_decode($this->ReadAttributeString($attributeName), true);
                        if (!in_array($battery['ID'], array_column($variables, 'ID'))) {
                            $variables[] = [
                                'ID'        => $battery['ID'],
                                'Name'      => $battery['Name'],
                                'Comment'   => $battery['Comment'],
                                'Timestamp' => date('d.m.Y, H:i:s')];
                            $this->WriteAttributeString($attributeName, json_encode($variables));
                        }
                    }
                }
            }

            ##### Low battery

            if ($actualStatus == 1) {
                $notificationName = 'ImmediateNotificationDeviceStatusLowBattery';
                //Check limitation
                if ($this->ReadPropertyBoolean('UseImmediateNotificationDeviceStateLimit')) {
                    //Add to immediate notification list
                    $attributeName = 'ImmediateNotificationListDeviceStatusLowBattery';
                    $variables = json_decode($this->ReadAttributeString($attributeName), true);
                    if (!in_array($battery['ID'], array_column($variables, 'ID'))) {
                        $notify = true;
                        $variables[] = [
                            'ID'        => $battery['ID'],
                            'Name'      => $battery['Name'],
                            'Comment'   => $battery['Comment'],
                            'Timestamp' => date('d.m.Y, H:i:s')];
                        $this->WriteAttributeString($attributeName, json_encode($variables));
                    }
                    //Add to daily notification list
                    $attributeName = 'DailyNotificationListDeviceStatusLowBattery';
                    $variables = json_decode($this->ReadAttributeString($attributeName), true);
                    if (!in_array($battery['ID'], array_column($variables, 'ID'))) {
                        $variables[] = [
                            'ID'        => $battery['ID'],
                            'Name'      => $battery['Name'],
                            'Comment'   => $battery['Comment'],
                            'Timestamp' => date('d.m.Y, H:i:s')];
                        $this->WriteAttributeString($attributeName, json_encode($variables));
                    }
                    //Add to weekly notification list
                    $attributeName = 'WeeklyNotificationListDeviceStatusLowBattery';
                    $variables = json_decode($this->ReadAttributeString($attributeName), true);
                    if (!in_array($battery['ID'], array_column($variables, 'ID'))) {
                        $variables[] = [
                            'ID'        => $battery['ID'],
                            'Name'      => $battery['Name'],
                            'Comment'   => $battery['Comment'],
                            'Timestamp' => date('d.m.Y, H:i:s')];
                        $this->WriteAttributeString($attributeName, json_encode($variables));
                    }
                }
            }

            ##### Update overdue

            if ($actualStatus == 2) {
                $notificationName = 'ImmediateNotificationDeviceStatusUpdateOverdue';
                //Check limitation
                if ($this->ReadPropertyBoolean('UseImmediateNotificationDeviceStateLimit')) {
                    //Add to immediate notification list
                    $attributeName = 'ImmediateNotificationListDeviceStatusUpdateOverdue';
                    $variables = json_decode($this->ReadAttributeString($attributeName), true);
                    if (!in_array($battery['ID'], array_column($variables, 'ID'))) {
                        $notify = true;
                        $variables[] = [
                            'ID'        => $battery['ID'],
                            'Name'      => $battery['Name'],
                            'Comment'   => $battery['Comment'],
                            'Timestamp' => date('d.m.Y, H:i:s')];
                        $this->WriteAttributeString($attributeName, json_encode($variables));
                    }
                    //Add to daily notification list
                    $attributeName = 'DailyNotificationListDeviceStatusUpdateOverdue';
                    $variables = json_decode($this->ReadAttributeString($attributeName), true);
                    if (!in_array($battery['ID'], array_column($variables, 'ID'))) {
                        $variables[] = [
                            'ID'        => $battery['ID'],
                            'Name'      => $battery['Name'],
                            'Comment'   => $battery['Comment'],
                            'Timestamp' => date('d.m.Y, H:i:s')];
                        $this->WriteAttributeString($attributeName, json_encode($variables));
                    }
                    //Add to weekly notification list
                    $attributeName = 'WeeklyNotificationListDeviceStatusUpdateOverdue';
                    $variables = json_decode($this->ReadAttributeString($attributeName), true);
                    if (!in_array($battery['ID'], array_column($variables, 'ID'))) {
                        $variables[] = [
                            'ID'        => $battery['ID'],
                            'Name'      => $battery['Name'],
                            'Comment'   => $battery['Comment'],
                            'Timestamp' => date('d.m.Y, H:i:s')];
                        $this->WriteAttributeString($attributeName, json_encode($variables));
                    }
                }
            }

            ##### Device status notification

            if ($notify && $notificationName != '' && $this->GetValue('Active')) {
                $id = $this->ReadPropertyInteger('ImmediateNotification');
                if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                    $notification = json_decode($this->ReadPropertyString($notificationName), true);
                    if ($notification[0]['Use']) {
                        $messageText = $notification[0]['MessageText'];
                        //Check for placeholder
                        if (strpos($messageText, '%1$s') !== false) {
                            $messageText = sprintf($messageText, $battery['Name']);
                        }
                        if ($notification[0]['UseTimestamp']) {
                            $messageText = $messageText . ' ' . date('d.m.Y, H:i:s');
                        }
                        $this->SendDebug(__FUNCTION__, 'Meldungstext: ' . $messageText, 0);
                        //WebFront notification
                        if ($notification[0]['UseWebFrontNotification']) {
                            @BN_SendWebFrontNotification($id, $notification[0]['WebFrontNotificationTitle'], "\n" . $messageText, $notification[0]['WebFrontNotificationIcon'], $notification[0]['WebFrontNotificationDisplayDuration']);
                        }
                        //WebFront push notification
                        if ($notification[0]['UseWebFrontPushNotification']) {
                            @BN_SendWebFrontPushNotification($id, $notification[0]['WebFrontPushNotificationTitle'], "\n" . $messageText, $notification[0]['WebFrontPushNotificationSound'], $notification[0]['WebFrontPushNotificationTargetID']);
                        }
                        //E-Mail
                        if ($notification[0]['UseMailer']) {
                            @BN_SendMailNotification($id, $notification[0]['Subject'], "\n\n" . $messageText);
                        }
                        //SMS
                        if ($notification[0]['UseSMS']) {
                            @BN_SendNexxtMobileSMS($id, $notification[0]['SMSTitle'], "\n\n" . $messageText);
                            @BN_SendSipgateSMS($id, $notification[0]['SMSTitle'], "\n\n" . $messageText);
                        }
                        //Telegram
                        if ($notification[0]['UseTelegram']) {
                            @BN_SendTelegramMessage($id, $notification[0]['TelegramTitle'], "\n\n" . $messageText);
                        }
                    }
                }
            }
        }
        return $result;
    }

    #################### Private

    /**
     * Checks for monitored variables.
     *
     * @return bool
     * false =  There are no monitored variables
     * true =   There are monitored variables
     * @throws Exception
     */
    private function CheckForExistingVariables(): bool
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $monitoredVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($monitoredVariables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                            if ($variable['CheckBattery'] || $variable['CheckUpdate']) {
                                return true;
                            }
                        }
                    }
                }
            }
        }
        $this->SendDebug(__FUNCTION__, 'Abbruch, Es werden keine Variablen überwacht!', 0);
        $this->SetDefault();
        return false;
    }

    /**
     * Gets the monitored variables and their status.
     *
     * @return string
     * @throws Exception
     */
    private function GetMonitoredVariables(): string
    {
        $result = [];
        $monitoredVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($monitoredVariables as $variable) {
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
            //Variable id exists
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $actualStatus = 0;
                $unicode = json_decode('"\u2705"'); // white_check_mark
                //Check for low battery
                if ($variable['CheckBattery']) {
                    if (IPS_IsConditionPassing($variable['PrimaryCondition'])) {
                        $actualStatus = 1;
                        $unicode = json_decode('"\u26a0\ufe0f"'); // warning
                    }
                }
                //Check for update overdue
                if ($variable['CheckUpdate']) {
                    $now = time();
                    $variableUpdate = IPS_GetVariable($id)['VariableUpdated'];
                    $dateDifference = ($now - $variableUpdate) / (60 * 60 * 24);
                    if ($dateDifference > $variable['UpdatePeriod']) {
                        $actualStatus = 2;
                        $unicode = json_decode('"\u2757"'); // heavy_exclamation_mark
                    }
                }
                //Checks are disabled
                if (!$variable['CheckBattery'] && !$variable['CheckUpdate']) {
                    $actualStatus = 3;
                    $unicode = json_decode('"\ud83d\udeab"'); # no_entry_sign
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
                $result[] = [
                    'ActualStatus'           => $actualStatus, //0 = OK, 1 = low battery, 2 = update overdue, 3 = checks are disabled
                    'Unicode'                => $unicode,
                    'ID'                     => $id,
                    'Name'                   => $variable['Designation'],
                    'Comment'                => $variable['Comment'],
                    'LastBatteryReplacement' => $lastBatteryReplacement];
            }
        }
        return json_encode($result);
    }
}