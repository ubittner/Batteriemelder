<?php

/**
 * @project       Batteriemelder/Batteriemelder/helper
 * @file          BATM_MonitoredVariables.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection SpellCheckingInspection */
/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait BATM_MonitoredVariables
{
    /**
     * Applies the determined variables to the trigger list.
     *
     * @param object $ListValues
     * @param bool $OverwriteVariableProfiles
     * false =  don't overwrite
     * true =   overwrite
     *
     * @return void
     * @throws ReflectionException
     * @throws Exception
     */
    public function ApplyDeterminedVariables(object $ListValues, bool $OverwriteVariableProfiles): void
    {
        $determinedVariables = [];
        $reflection = new ReflectionObject($ListValues);
        $property = $reflection->getProperty('array');
        $property->setAccessible(true);
        $variables = $property->getValue($ListValues);
        foreach ($variables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            $id = $variable['ID'];
            //Overwrite variable profiles
            if ($OverwriteVariableProfiles) {
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
                    @IPS_SetVariableCustomProfile($id, $profileName);
                }
            }
            $name = @IPS_GetName($id);
            $address = '';
            $lastBatteryReplacement = '{"year":0, "month":0, "day":0}';
            $parent = @IPS_GetParent($id);
            if ($parent > 1 && @IPS_ObjectExists($parent)) {
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
            if (IPS_GetVariable($id)['VariableType'] == 1) {
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
                            'variableID' => $id,
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
                'UseMultipleAlerts'      => false,
                'PrimaryCondition'       => json_encode($primaryCondition),
                'LastBatteryReplacement' => $lastBatteryReplacement];
        }
        //Get already listed variables
        $listedVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($determinedVariables as $determinedVariable) {
            $determinedVariableID = 0;
            if (array_key_exists('PrimaryCondition', $determinedVariable)) {
                $primaryCondition = json_decode($determinedVariable['PrimaryCondition'], true);
                if ($primaryCondition != '') {
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $determinedVariableID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        }
                    }
                }
            }
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
        if (empty($determinedVariables)) {
            return;
        }
        //Sort variables by name
        array_multisort(array_column($listedVariables, 'Designation'), SORT_ASC, $listedVariables);
        @IPS_SetProperty($this->InstanceID, 'TriggerList', json_encode(array_values($listedVariables)));
        if (@IPS_HasChanges($this->InstanceID)) {
            @IPS_ApplyChanges($this->InstanceID);
        }
    }

    /**
     * Checks the determination value for the variable.
     *
     * @param int $VariableDeterminationType
     * @return void
     */
    public function CheckVariableDeterminationValue(int $VariableDeterminationType): void
    {
        $profileSelection = false;
        $determinationValue = false;
        //Profile selection
        if ($VariableDeterminationType == 0) {
            $profileSelection = true;
        }
        //Custom ident
        if ($VariableDeterminationType == 10) {
            $this->UpdateFormfield('VariableDeterminationValue', 'caption', 'Identifikator');
            $determinationValue = true;
        }
        $this->UpdateFormfield('ProfileSelection', 'visible', $profileSelection);
        $this->UpdateFormfield('VariableDeterminationValue', 'visible', $determinationValue);
    }

    /**
     * Determines the variables.
     *
     * @param int $DeterminationType
     * @param string $DeterminationValue
     * @param string $ProfileSelection
     * @return void
     * @throws Exception
     */
    public function DetermineVariables(int $DeterminationType, string $DeterminationValue, string $ProfileSelection = ''): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SendDebug(__FUNCTION__, 'Auswahl: ' . $DeterminationType, 0);
        $this->SendDebug(__FUNCTION__, 'Identifikator: ' . $DeterminationValue, 0);
        //Set minimum an d maximum of existing variables
        $this->UpdateFormField('VariableDeterminationProgress', 'minimum', 0);
        $maximumVariables = count(IPS_GetVariableList());
        $this->UpdateFormField('VariableDeterminationProgress', 'maximum', $maximumVariables);
        //Determine variables first
        $determineIdent = false;
        $determineProfile = false;
        $determinedVariables = [];
        $passedVariables = 0;
        foreach (@IPS_GetVariableList() as $variable) {
            switch ($DeterminationType) {
                case 0: //Profile: Select profile
                    if ($ProfileSelection == '') {
                        $infoText = 'Abbruch, es wurde kein Profil ausgewählt!';
                        $this->UpdateFormField('InfoMessage', 'visible', true);
                        $this->UpdateFormField('InfoMessageLabel', 'caption', $infoText);
                        return;
                    } else {
                        $determineProfile = true;
                    }
                    break;

                case 1: //Profile: ~Battery
                case 2: //Profile: ~Battery.Reversed
                case 3: //Profile: BATM.Battery.Boolean
                case 4: //Profile: BATM.Battery.Boolean.Reversed
                case 5: //Profile: BATM.Battery.Integer
                case 6: //Profile: BATM.Battery.Integer.reversed
                    $determineProfile = true;
                    break;

                case 7: //Ident: LOWBAT
                case 8: //Ident: LOW_BAT
                case 9: //Ident: LOWBAT, LOW_BAT
                    $determineIdent = true;
                    break;

                case 10: //Custom Ident
                    if ($DeterminationValue == '') {
                        $infoText = 'Abbruch, es wurde kein Identifikator angegeben!';
                        $this->UpdateFormField('InfoMessage', 'visible', true);
                        $this->UpdateFormField('InfoMessageLabel', 'caption', $infoText);
                        return;
                    } else {
                        $determineIdent = true;
                    }
                    break;

            }
            $passedVariables++;
            $this->UpdateFormField('VariableDeterminationProgress', 'visible', true);
            $this->UpdateFormField('VariableDeterminationProgress', 'current', $passedVariables);
            $this->UpdateFormField('VariableDeterminationProgressInfo', 'visible', true);
            $this->UpdateFormField('VariableDeterminationProgressInfo', 'caption', $passedVariables . '/' . $maximumVariables);
            IPS_Sleep(10);

            ##### Profile

            //Determine via profile
            if ($determineProfile && !$determineIdent) {
                switch ($DeterminationType) {

                    case 0: //Select profile
                        $profileNames = $ProfileSelection;
                        break;

                    case 1:
                        $profileNames = '~Battery';
                        break;

                    case 2:
                        $profileNames = '~Battery.Reversed';
                        break;

                    case 3:
                        $profileNames = 'BATM.Battery.Boolean';
                        break;

                    case 4:
                        $profileNames = 'BATM.Battery.Boolean.Reversed';
                        break;

                    case 5:
                        $profileNames = 'BATM.Battery.Integer';
                        break;

                    case 6:
                        $profileNames = 'BATM.Battery.Integer.Reversed';
                        break;

                }
                if (isset($profileNames)) {
                    $profileNames = str_replace(' ', '', $profileNames);
                    $profileNames = explode(',', $profileNames);
                    foreach ($profileNames as $profileName) {
                        $variableData = IPS_GetVariable($variable);
                        if ($variableData['VariableCustomProfile'] == $profileName || $variableData['VariableProfile'] == $profileName) {
                            $location = @IPS_GetLocation($variable);
                            $determinedVariables[] = [
                                'Use'      => true,
                                'ID'       => $variable,
                                'Location' => $location];
                        }
                    }
                }
            }

            ##### Ident

            //Determine via ident
            if ($determineIdent && !$determineProfile) {
                switch ($DeterminationType) {
                    case 7:
                        $objectIdents = 'LOWBAT';
                        break;

                    case 8:
                        $objectIdents = 'LOW_BAT';
                        break;

                    case 9:
                        $objectIdents = 'LOWBAT, LOW_BAT';
                        break;

                    case 10: //Custom ident
                        $objectIdents = $DeterminationValue;
                        break;

                }
                if (isset($objectIdents)) {
                    $objectIdents = str_replace(' ', '', $objectIdents);
                    $objectIdents = explode(',', $objectIdents);
                    foreach ($objectIdents as $objectIdent) {
                        $object = @IPS_GetObject($variable);
                        if ($object['ObjectIdent'] == $objectIdent) {
                            $location = @IPS_GetLocation($variable);
                            $determinedVariables[] = [
                                'Use'      => true,
                                'ID'       => $variable,
                                'Location' => $location];
                        }
                    }
                }
            }
        }
        $amount = count($determinedVariables);
        //Get already listed variables
        $listedVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($listedVariables as $listedVariable) {
            if (array_key_exists('PrimaryCondition', $listedVariable)) {
                $primaryCondition = json_decode($listedVariable['PrimaryCondition'], true);
                if ($primaryCondition != '') {
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $listedVariableID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                            if ($listedVariableID > 1 && @IPS_ObjectExists($listedVariableID)) {
                                foreach ($determinedVariables as $key => $determinedVariable) {
                                    $determinedVariableID = $determinedVariable['ID'];
                                    if ($determinedVariableID > 1 && @IPS_ObjectExists($determinedVariableID)) {
                                        //Check if variable id is already a listed variable id
                                        if ($determinedVariableID == $listedVariableID) {
                                            unset($determinedVariables[$key]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        if (empty($determinedVariables)) {
            $this->UpdateFormField('VariableDeterminationProgress', 'visible', false);
            $this->UpdateFormField('VariableDeterminationProgressInfo', 'visible', false);
            if ($amount > 0) {
                $infoText = 'Es wurden keine weiteren Variablen gefunden!';
            } else {
                $infoText = 'Es wurden keine Variablen gefunden!';
            }
            $this->UpdateFormField('InfoMessage', 'visible', true);
            $this->UpdateFormField('InfoMessageLabel', 'caption', $infoText);
            return;
        }
        $determinedVariables = array_values($determinedVariables);
        $this->UpdateFormField('DeterminedVariableList', 'visible', true);
        $this->UpdateFormField('DeterminedVariableList', 'rowCount', count($determinedVariables));
        $this->UpdateFormField('DeterminedVariableList', 'values', json_encode($determinedVariables));
        $this->UpdateFormField('OverwriteVariableProfiles', 'visible', true);
        $this->UpdateFormField('ApplyPreTriggerValues', 'visible', true);
    }

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
        $maximumVariables = count($monitoredVariables);
        $this->UpdateFormField('VariableProfileProgress', 'minimum', 0);
        $this->UpdateFormField('VariableProfileProgress', 'maximum', $maximumVariables);
        $passedVariables = 0;
        if (!empty($monitoredVariables)) {
            foreach ($monitoredVariables as $variable) {
                $passedVariables++;
                $this->UpdateFormField('VariableProfileProgress', 'visible', true);
                $this->UpdateFormField('VariableProfileProgress', 'current', $passedVariables);
                $this->UpdateFormField('VariableProfileProgressInfo', 'visible', true);
                $this->UpdateFormField('VariableProfileProgressInfo', 'caption', $passedVariables . '/' . $maximumVariables);
                IPS_Sleep(200);
                //Primary condition
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                            if ($id > 1 && @IPS_ObjectExists($id)) {
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
        $this->UpdateFormField('VariableProfileProgress', 'visible', false);
        $this->UpdateFormField('VariableProfileProgressInfo', 'visible', false);
        $this->UIShowMessage('Die Variablenprofile wurden zugewiesen!');
    }

    /**
     * Gets the actual variable states
     *
     * @return void
     * @throws Exception
     */
    public function GetActualVariableStates(): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->UpdateFormField('ActualVariableStatesConfigurationButton', 'visible', false);
        $actualVariableStates = [];
        $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($variables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            $sensorID = 0;
            if ($variable['PrimaryCondition'] != '') {
                $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                if (array_key_exists(0, $primaryCondition)) {
                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                        $sensorID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                    }
                }
            }
            //Check conditions first
            $conditions = true;
            if ($sensorID <= 1 || !@IPS_ObjectExists($sensorID)) { //0 = main category, 1 = none
                $conditions = false;
            }
            $variableDesignation = $variable['Designation'];
            $variableComment = $variable['Comment'];
            //Last battery replacement
            $lastBatteryReplacement = 'Nie';
            $replacementDate = json_decode($variable['LastBatteryReplacement']);
            $lastBatteryReplacementYear = $replacementDate->year;
            $lastBatteryReplacementMonth = $replacementDate->month;
            $lastBatteryReplacementDay = $replacementDate->day;
            if ($lastBatteryReplacementYear != 0 && $lastBatteryReplacementMonth != 0 && $lastBatteryReplacementDay != 0) {
                $lastBatteryReplacement = sprintf('%02d', $lastBatteryReplacementDay) . '.' . sprintf('%02d', $lastBatteryReplacementMonth) . '.' . $lastBatteryReplacementYear;
            }
            //Battery type
            $batteryType = $variable['BatteryType'];
            if ($batteryType == '') {
                $batteryType = $variable['UserDefinedBatteryType'];
            }
            $stateName = 'fehlerhaft';
            if ($conditions) {
                $stateName = $this->ReadPropertyString('BatteryOKStatusText');
                if (IPS_IsConditionPassing($variable['PrimaryCondition'])) {
                    $stateName = $this->ReadPropertyString('LowBatteryStatusText');
                }
            }
            $actualVariableStates[] = ['ActualStatus' => $stateName, 'SensorID' => $sensorID, 'Designation' => $variableDesignation, 'Comment' => $variableComment, 'BatteryType' => $batteryType, 'LastBatteryReplacement' => $lastBatteryReplacement];
        }
        $amount = count($actualVariableStates);
        if ($amount == 0) {
            $amount = 1;
        }
        $this->UpdateFormField('ActualVariableStates', 'visible', true);
        $this->UpdateFormField('ActualVariableStates', 'rowCount', $amount);
        $this->UpdateFormField('ActualVariableStates', 'values', json_encode($actualVariableStates));
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
            $this->UIShowMessage('Abbruch, bitte wählen Sie eine Kategorie aus!');
            return;
        }
        $icon = 'Battery';
        //Get all monitored variables
        $monitoredVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        $maximumVariables = count($monitoredVariables);
        $this->UpdateFormField('VariableLinkProgress', 'minimum', 0);
        $this->UpdateFormField('VariableLinkProgress', 'maximum', $maximumVariables);
        $passedVariables = 0;
        $targetIDs = [];
        $i = 0;
        foreach ($monitoredVariables as $variable) {
            if ($variable['Use']) {
                $passedVariables++;
                $this->UpdateFormField('VariableLinkProgress', 'visible', true);
                $this->UpdateFormField('VariableLinkProgress', 'current', $passedVariables);
                $this->UpdateFormField('VariableLinkProgressInfo', 'visible', true);
                $this->UpdateFormField('VariableLinkProgressInfo', 'caption', $passedVariables . '/' . $maximumVariables);
                IPS_Sleep(200);
                //Primary condition
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                            if ($id > 1 && @IPS_ObjectExists($id)) {
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
        $this->UpdateFormField('VariableLinkProgress', 'visible', false);
        $this->UpdateFormField('VariableLinkProgressInfo', 'visible', false);
        $this->UIShowMessage('Die Variablenverknüpfungen wurden erfolgreich erstellt!');
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
                    //Remove from all lists, immediate, daily and weekly critical lists
                    $lists = ['ImmediateNotificationListDeviceStatusLowBattery', 'ImmediateNotificationListDeviceStatusNormal', 'DailyNotificationListDeviceStatusLowBattery', 'WeeklyNotificationListDeviceStatusLowBattery'];
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
            $data[$index]['Use'] = $variable['Use'];
            $data[$index]['Designation'] = $variable['Designation'];
            $data[$index]['Comment'] = $variable['Comment'];
            $data[$index]['BatteryType'] = $variable['BatteryType'];
            $data[$index]['UserDefinedBatteryType'] = $variable['UserDefinedBatteryType'];
            $data[$index]['UseMultipleAlerts'] = $variable['UseMultipleAlerts'];
            $data[$index]['PrimaryCondition'] = $variable['PrimaryCondition'];
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
        //Enter semaphore
        if (!$this->LockSemaphore('CheckBatteries')) {
            $this->SendDebug(__FUNCTION__, 'Abort, Semaphore reached!', 0);
            $this->UnlockSemaphore('CheckBatteries');
            return false;
        }
        $monitoredVariables = json_decode($this->GetMonitoredVariables(), true);
        //Sort variables by name and rebase
        array_multisort(array_column($monitoredVariables, 'Name'), SORT_ASC, $monitoredVariables);
        $monitoredVariables = array_values($monitoredVariables);

        ##### Overall status

        $actualOverallStatus = 0;
        $result = true;
        if (in_array(1, array_column($monitoredVariables, 'ActualStatus'))) {
            $actualOverallStatus = 1;
            $result = false;
        }
        if ($this->GetValue('Status') != $actualOverallStatus) {
            $this->SetValue('Status', $actualOverallStatus);
        }

        ##### Triggering detector

        $name = '';
        foreach ($monitoredVariables as $monitoredVariable) {
            if ($monitoredVariable['ActualStatus'] == 1) {
                $name = $monitoredVariable['Name'];
                break;
            }
        }
        if ($this->GetValue('TriggeringDetector') != $name) {
            $this->SetValue('TriggeringDetector', $name);
        }

        ##### Last Update

        $this->SetValue('LastUpdate', date('d.m.Y H:i:s'));

        ##### Battery list

        $string = '';
        if ($this->ReadPropertyBoolean('EnableBatteryList')) {
            $string = "<table style='width: 100%; border-collapse: collapse;'>";
            $string .= '<tr><td><b>Status</b></td><td><b>Name</b></td><td><b>Bemerkung</b></td><td><b>Batterietyp</b></td><td><b>ID</b></td><td><b>Letzter Batteriewechsel</b></td></tr>';
            if (!empty($monitoredVariables)) {
                $data = 0;
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
            $actualStatus = $monitoredVariable['ActualStatus']; //0 = OK, 1 = low battery

            ### Battery OK

            if ($actualStatus == 0) {
                $timeStamp = date('d.m.Y, H:i:s');
                $statusChanged = false;
                //Check if status was low battery before
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
                                //Timestamp
                                if ($notification['UseBatteryOKTimestamp']) {
                                    $text = $text . ', ' . $timeStamp;
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
                                //Timestamp
                                if ($pushNotification['UseBatteryOKTimestamp']) {
                                    $text = $text . ', ' . $timeStamp;
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
                                    if ($batteryType != '') {
                                        $lineText = $lineText . ', Batterietyp: ' . $batteryType;
                                    }
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
                            //Battery type
                            $batteryType = $monitoredVariable['BatteryType'];
                            if ($notification['UseLowBatteryBatteryType']) {
                                if ($batteryType != '') {
                                    $text = $text . ', ' . $batteryType;
                                }
                            }
                            //Timestamp
                            if ($notification['UseLowBatteryTimestamp']) {
                                $text = $text . ', ' . $timeStamp;
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
                            //Battery type
                            $batteryType = $monitoredVariable['BatteryType'];
                            if ($pushNotification['UseLowBatteryBatteryType']) {
                                if ($batteryType != '') {
                                    $text = $text . ', ' . $batteryType;
                                }
                            }
                            //Timestamp
                            if ($pushNotification['UseLowBatteryTimestamp']) {
                                $text = $text . ', ' . $timeStamp;
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
                            $name = $monitoredVariable['Name'];
                            if ($monitoredVariable['Comment'] != '') {
                                $name = $name . ', ' . $monitoredVariable['Comment'];
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
                                if ($batteryType != '') {
                                    $lineText = $lineText . ', Batterietyp: ' . $batteryType;
                                }
                            }
                            $lowBatteryMessageText .= $lineText . "\n";
                            $scriptText = 'MA_SendMessage(' . $mailerID . ', "' . $mailer['Subject'] . '", "' . $lowBatteryMessageText . '");';
                            @IPS_RunScriptText($scriptText);
                        }
                    }
                }
            }
        }
        //Leave semaphore
        $this->UnlockSemaphore('CheckBatteries');
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
                        if ($id <= 1 || @!IPS_ObjectExists($id)) {
                            continue;
                        }
                    }
                }
            }
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $actualStatus = 0;
                //Check low battery
                if (IPS_IsConditionPassing($variable['PrimaryCondition'])) {
                    $actualStatus = 1;
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
                    'ActualStatus'           => $actualStatus, //0 = OK, 1 = low battery
                    'ID'                     => $id,
                    'Name'                   => $variable['Designation'],
                    'Comment'                => $variable['Comment'],
                    'BatteryType'            => $batteryType,
                    'LastBatteryReplacement' => $lastBatteryReplacement];
            }
        }
        return json_encode($result);
    }
}