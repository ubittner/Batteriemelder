<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait BATM_MonitoredVariables
{
    public function CheckVariableDeterminationValue(int $VariableDeterminationType): void
    {
        $profileSelection = false;
        $determinationValue = false;
        //Profile selection
        if ($VariableDeterminationType == 0) {
            $profileSelection = true;
        }
        //Custom ident
        if ($VariableDeterminationType == 4) {
            $this->UpdateFormfield('VariableDeterminationValue', 'caption', 'Identifikator');
            $determinationValue = true;
        }
        $this->UpdateFormfield('ProfileSelection', 'visible', $profileSelection);
        $this->UpdateFormfield('VariableDeterminationValue', 'visible', $determinationValue);
    }

    public function DetermineVariables(int $DeterminationType, string $DeterminationValue, string $ProfileSelection = ''): void
    {
        //Set the minimum and maximum of existing variables
        $this->UpdateFormField('VariableDeterminationProgress', 'minimum', 0);
        $maximumVariables = count(IPS_GetVariableList());
        $this->UpdateFormField('VariableDeterminationProgress', 'maximum', $maximumVariables);
        //Determine the variables first
        $determineIdent = false;
        $determineProfile = false;
        $determinedVariables = [];
        $passedVariables = 0;
        foreach (@IPS_GetVariableList() as $variable) {
            switch ($DeterminationType) {
                case 0: # Profile: Select profile
                    if ($ProfileSelection == '') {
                        $infoText = 'Abbruch, es wurde kein Profil ausgewählt!';
                        $this->UpdateFormField('InfoMessage', 'visible', true);
                        $this->UpdateFormField('InfoMessageLabel', 'caption', $infoText);
                        return;
                    } else {
                        $determineProfile = true;
                    }
                    break;

                case 1: # Ident: LOWBAT
                case 2: # Ident: LOW_BAT
                case 3: # Ident: LOWBAT, LOW_BAT
                    $determineIdent = true;
                    break;

                case 4: # Custom Ident
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

            //Determine the variables via profile
            if ($determineProfile && !$determineIdent) {
                //Select profile
                if ($DeterminationType == 0) {
                    $profileNames = $ProfileSelection;
                }
                if (isset($profileNames)) {
                    $profileNames = str_replace(' ', '', $profileNames);
                    $profileNames = explode(',', $profileNames);
                    foreach ($profileNames as $profileName) {
                        $variableData = IPS_GetVariable($variable);
                        if ($variableData['VariableCustomProfile'] == $profileName || $variableData['VariableProfile'] == $profileName) {
                            $location = @IPS_GetLocation($variable);
                            $determinedVariables[] = [
                                'Use'      => false,
                                'ID'       => $variable,
                                'Location' => $location];
                        }
                    }
                }
            }

            ##### Ident

            //Determine the variables via ident
            if ($determineIdent && !$determineProfile) {
                switch ($DeterminationType) {
                    case 1:
                        $objectIdents = 'LOWBAT';
                        break;

                    case 2:
                        $objectIdents = 'LOW_BAT';
                        break;

                    case 3:
                        $objectIdents = 'LOWBAT, LOW_BAT';
                        break;

                    case 4: //Custom ident
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
        //Get the already listed variables
        $listedVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
        foreach ($listedVariables as $listedVariable) {
            $listedVariableID = $this->GetVariableIDFromCondition($listedVariable['PrimaryCondition']);
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
        $amount = count($determinedVariables);
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
        $this->UpdateFormField('DeterminedVariableList', 'rowCount', count($determinedVariables));
        $this->UpdateFormField('DeterminedVariableList', 'values', json_encode($determinedVariables));
        $this->UpdateFormField('DeterminedVariableList', 'visible', true);
        $this->UpdateFormField('OverwriteVariableProfiles', 'visible', true);
        $this->UpdateFormField('ApplyPreTriggerValues', 'visible', true);
    }

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
                    case 0: # Boolean
                        $profileName = 'Battery.Boolean';
                        break;

                    case 1: # Integer
                        $profileName = 'Battery.Integer';
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
                if ($parentObject['ObjectType'] == 1) { # 1 = instance
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
            $determinedVariableID = $this->GetVariableIDFromCondition($determinedVariable['PrimaryCondition']);
            if ($determinedVariableID > 1 && @IPS_ObjectExists($determinedVariableID)) {
                //Check variable id with already listed variable ids
                $add = true;
                foreach ($listedVariables as $listedVariable) {
                    $listedVariableID = $this->GetVariableIDFromCondition($listedVariable['PrimaryCondition']);
                    if ($listedVariableID > 1 && @IPS_ObjectExists($determinedVariableID)) {
                        if ($determinedVariableID == $listedVariableID) {
                            $add = false;
                        }
                    }
                }
                //Add a new variable to already listed variables
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

    public function DetermineActualBatteryStates(): void
    {
        $actualBatteryStates = [];
        $this->UpdateFormField('ActualVariableStatesConfigurationButton', 'visible', false);
        $monitoredVariableStates = json_decode($this->CheckBatteries(), true);
        array_multisort(array_column($monitoredVariableStates, 'InternalStatus'), SORT_DESC, $monitoredVariableStates);
        $monitoredVariableStates = array_values($monitoredVariableStates);
        foreach ($monitoredVariableStates as $variable) {
            $actualBatteryStates[] = ['ActualStatus' => $variable['UserDefinedStatus'], 'SensorID' => $variable['ID'], 'Designation' => $variable['Name'], 'Comment' => $variable['Comment'], 'BatteryType' => $variable['BatteryType'], 'LastBatteryReplacement' => $variable['LastBatteryReplacement'], 'LastUpdate' => $variable['LastUpdateText'], 'UpdateOverdue' => $variable['UpdateOverdueText']];
        }
        $amount = count($actualBatteryStates);
        if ($amount == 0) {
            $amount = 1;
        }
        $this->UpdateFormField('ActualVariableStates', 'rowCount', $amount);
        $this->UpdateFormField('ActualVariableStates', 'values', json_encode($actualBatteryStates));
    }

    public function AssignVariableProfile(object $ListValues): void
    {
        $reflection = new ReflectionObject($ListValues);
        $property = $reflection->getProperty('array');
        $property->setAccessible(true);
        $variables = $property->getValue($ListValues);
        $amountVariables = 0;
        foreach ($variables as $variable) {
            if ($variable['Use']) {
                $amountVariables++;
            }
        }
        if ($amountVariables == 0) {
            $this->UpdateFormField('InfoMessage', 'visible', true);
            $this->UpdateFormField('InfoMessageLabel', 'caption', 'Es wurden keine Variablen ausgewählt!');
            return;
        }
        $maximumVariables = $amountVariables;
        $this->UpdateFormField('VariableProfileProgress', 'minimum', 0);
        $this->UpdateFormField('VariableProfileProgress', 'maximum', $maximumVariables);
        $passedVariables = 0;
        foreach ($variables as $variable) {
            if (!$variable['Use']) {
                continue;
            }
            $passedVariables++;
            $this->UpdateFormField('VariableProfileProgress', 'visible', true);
            $this->UpdateFormField('VariableProfileProgress', 'current', $passedVariables);
            $this->UpdateFormField('VariableProfileProgressInfo', 'visible', true);
            $this->UpdateFormField('VariableProfileProgressInfo', 'caption', $passedVariables . '/' . $maximumVariables);
            IPS_Sleep(250);
            $id = $variable['SensorID'];
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $object = IPS_GetObject($id)['ObjectType'];
                //0: Category, 1: Instance, 2: Variable, 3: Script, 4: Event, 5: Media, 6: Link
                if ($object == 2) {
                    $variableType = IPS_GetVariable($id)['VariableType'];
                    switch ($variableType) {
                        //0: Boolean, 1: Integer, 2: Float, 3: String
                        case 0:
                            $profileName = 'Battery.Boolean';
                            if ($variable['UseReversedProfile']) {
                                $profileName = 'Battery.Boolean.Reversed';
                            }
                            break;

                        case 1:
                            $profileName = 'Battery.Integer';
                            if ($variable['UseReversedProfile']) {
                                $profileName = 'Battery.Integer.Reversed';
                            }
                            break;

                        default:
                            $profileName = '';
                    }
                    if (!empty($profileName)) {
                        //Assign profile
                        IPS_SetVariableCustomProfile($id, $profileName);
                        //Deactivate standard action
                        IPS_SetVariableCustomAction($id, 1);
                    }
                }
            }
        }
        $this->UpdateFormField('VariableProfileProgress', 'visible', false);
        $this->UpdateFormField('VariableProfileProgressInfo', 'visible', false);
        $this->ReloadConfig();
    }

    public function CreateVariableLinks(int $LinkCategory, object $ListValues): void
    {
        if ($LinkCategory == 1 || @!IPS_ObjectExists($LinkCategory)) {
            $this->UIShowMessage('Abbruch, bitte wählen Sie eine Kategorie aus!');
            return;
        }
        $reflection = new ReflectionObject($ListValues);
        $property = $reflection->getProperty('array');
        $property->setAccessible(true);
        $variables = $property->getValue($ListValues);
        $amountVariables = 0;
        foreach ($variables as $variable) {
            if ($variable['Use']) {
                $amountVariables++;
            }
        }
        if ($amountVariables == 0) {
            $this->UpdateFormField('InfoMessage', 'visible', true);
            $this->UpdateFormField('InfoMessageLabel', 'caption', 'Es wurden keine Variablen ausgewählt!');
            return;
        }
        $maximumVariables = $amountVariables;
        $this->UpdateFormField('VariableLinkProgress', 'minimum', 0);
        $this->UpdateFormField('VariableLinkProgress', 'maximum', $maximumVariables);
        $passedVariables = 0;
        $targetIDs = [];
        $i = 0;
        foreach ($variables as $variable) {
            if ($variable['Use']) {
                $passedVariables++;
                $this->UpdateFormField('VariableLinkProgress', 'visible', true);
                $this->UpdateFormField('VariableLinkProgress', 'current', $passedVariables);
                $this->UpdateFormField('VariableLinkProgressInfo', 'visible', true);
                $this->UpdateFormField('VariableLinkProgressInfo', 'caption', $passedVariables . '/' . $maximumVariables);
                IPS_Sleep(200);
                $id = $variable['SensorID'];
                if ($id > 1 && @IPS_ObjectExists($id)) {
                    $targetIDs[$i] = ['name' => $variable['Name'], 'targetID' => $id];
                    $i++;
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
            }
        }
        $this->UpdateFormField('VariableLinkProgress', 'visible', false);
        $this->UpdateFormField('VariableLinkProgressInfo', 'visible', false);
        $infoText = 'Die Variablenverknüpfung wurde erfolgreich erstellt!';
        if ($amountVariables > 1) {
            $infoText = 'Die Variablenverknüpfungen wurden erfolgreich erstellt!';
        }
        $this->UIShowMessage($infoText);
    }

    public function CheckBatteries(): string
    {
        //Enter semaphore first
        if (!$this->LockSemaphore('CheckBatteries')) {
            $this->SendDebug(__FUNCTION__, 'Abort, Semaphore reached!', 0);
            $this->UnlockSemaphore('CheckBatteries');
            return '[]';
        }
        $timestamp = date('d.m.Y, H:i:s');
        //Init the monitored variables, this will be our internal battery list with additional information
        $monitoredVariables = [];
        //Get the zero-timestamp helper, it includes an initial timestamp from variables that were never updated before
        $timestampHelper = json_decode($this->ReadAttributeString('ZeroTimestampHelper'), true);
        //Get the monitored variables from the configuration
        foreach (json_decode($this->ReadPropertyString('TriggerList'), true) as $variable) {
            //Get the variable id first
            $id = $this->GetVariableIDFromCondition($variable['PrimaryCondition']);
            //The variable is not monitored
            if (!$variable['Use']) {
                //Remove the variable from the zero-timestamp helper if it is present there
                $this->RemoveVariableFromZeroTimestampHelper($id);
                continue;
            }
            //Variable is monitored and exists
            if ($id > 1 && @IPS_ObjectExists($id)) {
                $internalStatus = 0; # 0 = Battery OK
                $variableUpdated = IPS_GetVariable($id)['VariableUpdated'];
                $updateOverdue = 0;
                //Check update overdue to assume that the battery is empty
                if (array_key_exists('CheckUpdateOverdue', $variable)) {
                    if ($variable['CheckUpdateOverdue']) {
                        //Variable was never updated before
                        if ($variableUpdated == 0) {
                            if (!array_key_exists($id, $timestampHelper)) {
                                $variableUpdated = time();
                                $this->AddVariableToZeroTimestampHelper($id, $variableUpdated);
                            } else {
                                $variableUpdated = $timestampHelper[$id];
                            }
                        } else {
                            //Remove the variable from the timestamp helper
                            $this->RemoveVariableFromZeroTimestampHelper($id);
                        }
                        //Check update overdue
                        if (array_key_exists('OverdueTimeBase', $variable) && array_key_exists('OverdueTimeValue', $variable)) { //Not present in versions before 4.0-18
                            $watchTime = $this->GetWatchTime($variable['OverdueTimeBase'], $variable['OverdueTimeValue']);
                            $watchTimeBorder = time() - $watchTime;
                            if ($variableUpdated < $watchTimeBorder) {
                                $internalStatus = 2;
                                $updateOverdue = time() - $variableUpdated;
                            }
                        }
                    } else {
                        //Remove the variable from the zero-timestamp helper if it is present there
                        $this->RemoveVariableFromZeroTimestampHelper($id);
                    }
                }
                //Check for low battery
                if ($internalStatus != 2) { # 2 = Empty battery
                    if (IPS_IsConditionPassing($variable['PrimaryCondition'])) {
                        $internalStatus = 1; # 1 = Low battery
                    }
                }
                //Get the battery type
                $batteryType = $variable['BatteryType'];
                if ($batteryType == '') {
                    $batteryType = $variable['UserDefinedBatteryType'];
                }
                //Add to the monitored variables list
                $monitoredVariables[] = [
                    'Timestamp'              => $timestamp,
                    'InternalStatus'         => $internalStatus, # 0 = Battery OK, 1 = Low battery, 2 = Empty battery
                    'Status'                 => $this->GetStatusTextFromInternalStatus($internalStatus), # 'BatteryOK', LowBattery', 'EmptyBattery'
                    'UserDefinedStatus'      => $this->GetUserDefinedStatus($internalStatus),
                    'ID'                     => $id,
                    'Name'                   => $variable['Designation'],
                    'Comment'                => $variable['Comment'],
                    'BatteryType'            => $batteryType,
                    'LastBatteryReplacement' => $this->GetLastBatteryReplacement($variable['LastBatteryReplacement']),
                    'LastUpdate'             => $variableUpdated,
                    'LastUpdateText'         => $this->GetLastUpdateText($variableUpdated),
                    'UpdateOverdue'          => $updateOverdue,
                    'UpdateOverdueText'      => $this->GetUpdateOverdueText($updateOverdue)
                ];
            }
        }
        //Sort the monitored variables by name
        array_multisort(array_column($monitoredVariables, 'Name'), SORT_ASC, $monitoredVariables);
        $monitoredVariables = array_values($monitoredVariables);
        //Write the monitored variables state list to the attribute
        $this->WriteAttributeString('MonitoredVariables', json_encode($monitoredVariables));
        //Set the overall status on the user interface
        $overallStatus = 0;
        if (in_array(1, array_column($monitoredVariables, 'InternalStatus'))) {
            $overallStatus = 1;
        }
        if (in_array(2, array_column($monitoredVariables, 'InternalStatus'))) {
            $overallStatus = 2;
        }
        if ($this->GetValue('Status') != $overallStatus) {
            $this->SetValue('Status', $overallStatus);
        }
        //Set the triggering detector on the user interface
        $name = '';
        $lowBattery = false;
        $lowBatteryName = '';
        $emptyBattery = false;
        $emptyBatteryName = '';
        foreach ($monitoredVariables as $state) {
            if ($state['Status'] == 1) {
                $lowBattery = true;
                $lowBatteryName = $state['Name'];
                break;
            }
        }
        foreach ($monitoredVariables as $state) {
            if ($state['Status'] == 2) {
                $emptyBattery = true;
                $emptyBatteryName = $state['Name'];
                break;
            }
        }
        if ($lowBattery) {
            $name = $lowBatteryName;
        }
        if ($emptyBattery) {
            $name = $emptyBatteryName;
        }
        //Set the triggering detector
        if ($this->GetValue('TriggeringDetector') != $name) {
            $this->SetValue('TriggeringDetector', $name);
        }
        //Set the last update on the user interface
        $this->SetValue('LastUpdate', date('d.m.Y H:i:s'));
        //Update the battery list on the user interface
        $this->UpdateBatteryList(json_encode($monitoredVariables));
        //Check if the variables are still monitored, otherwise remove from notification lists
        $this->CleanupNotificationLists(json_encode($monitoredVariables));
        //Update the notification lists
        $this->UpdateNotificationLists(json_encode($monitoredVariables));
        //Leave semaphore
        $this->UnlockSemaphore('CheckBatteries');
        //Return the monitored variables state list as a JSON encoded string
        return json_encode($monitoredVariables);
    }

    public function UpdateBatteryReplacement(int $VariableID): void
    {
        $data = [];
        if ($VariableID > 1 && @IPS_ObjectExists($VariableID)) {
            //Check the actual status and remove it from the critical lists if battery status is okay again
            foreach (json_decode($this->CheckBatteries(), true) as $monitoredVariable) {
                if ($monitoredVariable['ID'] == $VariableID) {
                    if ($monitoredVariable['Status'] == 'BatteryOK') {
                        //Remove from all notification lists: immediate, daily and weekly
                        $lists = ['ImmediateNotificationListDeviceStatusLowBattery', 'ImmediateNotificationListDeviceStatusBatteryOK', 'DailyNotificationListDeviceStatusLowBattery', 'WeeklyNotificationListDeviceStatusLowBattery'];
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
            //Update the trigger list configuration for this variable
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
                if (array_key_exists('CheckUpdateOverdue', $variable)) { //Not present in versions before 4.0-18
                    $data[$index]['CheckUpdateOverdue'] = $variable['CheckUpdateOverdue'];
                    $data[$index]['OverdueTimeValue'] = $variable['OverdueTimeValue'];
                    $data[$index]['OverdueTimeBase'] = $variable['OverdueTimeBase'];
                }
            }
            IPS_SetProperty($this->InstanceID, 'TriggerList', json_encode($data));
            if (IPS_HasChanges($this->InstanceID)) {
                IPS_ApplyChanges($this->InstanceID);
            }
        }
    }

    ########## Private

    protected function GetVariableIDFromCondition(string $Condition): int
    {
        $id = 0;
        $Condition = json_decode($Condition, true);
        if (array_key_exists(0, $Condition)) {
            if (array_key_exists(0, $Condition[0]['rules']['variable'])) {
                $id = $Condition[0]['rules']['variable'][0]['variableID'];
            }
        }
        return $id;
    }

    private function AddVariableToZeroTimestampHelper(int $VariableID, int $Timestamp): void
    {
        $timestampHelper = json_decode($this->ReadAttributeString('ZeroTimestampHelper'), true);
        $timestampHelper[$VariableID] = $Timestamp;
        $this->WriteAttributeString('ZeroTimestampHelper', json_encode($timestampHelper));
    }

    private function RemoveVariableFromZeroTimestampHelper(int $VariableID): void
    {
        $timestampHelper = json_decode($this->ReadAttributeString('ZeroTimestampHelper'), true);
        if (array_key_exists($VariableID, $timestampHelper)) {
            unset($timestampHelper[$VariableID]);
        }
        $this->WriteAttributeString('ZeroTimestampHelper', json_encode($timestampHelper));
    }

    private function GetUserDefinedStatus(int $Status): string
    {
        switch ($Status) {
            case 0:
                $statusText = $this->ReadPropertyString('BatteryOKStatusText');
                break;

            case 1:
                $statusText = $this->ReadPropertyString('LowBatteryStatusText');
                break;

            case 2:
                $statusText = $this->ReadPropertyString('EmptyBatteryStatusText');
                break;

            default:
                $statusText = 'Unbekannt';
        }
        return $statusText;

    }

    private function GetLastUpdateText(int $Timestamp): string
    {
        if ($Timestamp == 0) {
            return 'Nie';
        }
        return date('d.m.Y H:i:s', $Timestamp);
    }

    private function GetWatchTime(int $TimeBase, int $TimeValue): int
    {
        switch ($TimeBase) {
            case 1: //Minutes
                return $TimeValue * 60;

            case 2: //Hours
                return $TimeValue * 3600;

            case 3: //Days
                return $TimeValue * 86400;

            default: //Seconds
                return $TimeValue;
        }
    }

    private function GetUpdateOverdueText(int $OverdueTime): string
    {
        $template = '';
        $number = 0;
        if ($OverdueTime == 0) {
            return 'Nie';
        } elseif ($OverdueTime >= 1 && $OverdueTime < 60) {
            return 'Gerade eben';
        } elseif (($OverdueTime > 60) && ($OverdueTime < (60 * 60))) {
            $template = '%s Minute';
            $number = floor($OverdueTime / 60);
            if ($OverdueTime >= (2 * 60)) {
                $template .= 'n';
            }
        } elseif (($OverdueTime > (60 * 60)) && ($OverdueTime < (24 * 60 * 60))) {
            $template = '%s Stunde';
            $number = floor($OverdueTime / (60 * 60));
            if ($OverdueTime >= (2 * 60 * 60)) {
                $template .= 'n';
            }
        } elseif ($OverdueTime > (24 * 60 * 60)) {
            $template = '%s Tag';
            $number = floor($OverdueTime / (24 * 60 * 60));
            if ($OverdueTime >= (2 * 24 * 60 * 60)) {
                $template .= 'e';
            }
        }
        return sprintf($template, number_format($number, 0, '', '.'));
    }

    private function GetLastBatteryReplacement(string $LastBatteryReplacement): string
    {
        $lastReplacement = 'Nie';
        $replacementDate = json_decode($LastBatteryReplacement, true);
        $year = $replacementDate['year'];
        $month = $replacementDate['month'];
        $day = $replacementDate['day'];
        if ($year != 0 && $month != 0 && $day != 0) {
            $lastReplacement = sprintf('%02d', $day) . '.' . sprintf('%02d', $month) . '.' . $year;
        }
        return $lastReplacement;
    }

    private function UpdateBatteryList(string $BatteryList): void
    {
        //Check the battery list first
        if ($BatteryList == '' || !$this->IsStringJsonEncoded($BatteryList)) {
            return;
        }
        $monitoredVariables = json_decode($BatteryList, true);
        //Sort variables by name and rebase
        array_multisort(array_column($monitoredVariables, 'Name'), SORT_ASC, $monitoredVariables);
        $monitoredVariables = array_values($monitoredVariables);
        $string = '';
        //Check whether the battery list is activated for the UI
        if ($this->ReadPropertyBoolean('EnableBatteryList')) {
            $string = "<table style='width: 100%; border-collapse: collapse;'>";
            $string .= '<tr><td><b>Status</b></td><td><b>Bezeichnung</b></td><td><b>Bemerkung</b></td><td><b>Batterietyp</b></td><td><b>ID</b></td><td><b>Letzter Batteriewechsel</b></td></tr>';
            if (!empty($monitoredVariables)) {
                $data = 0;
                $batteryStates[2] = 'EmptyBattery';
                $batteryStates[1] = 'LowBattery';
                $batteryStates[0] = 'BatteryOK';
                foreach ($batteryStates as $key => $batteryState) {
                    if ($this->ReadPropertyBoolean('Enable' . $batteryState)) {
                        $spacer = false;
                        if (in_array($key, array_column($monitoredVariables, 'InternalStatus'))) {
                            foreach ($monitoredVariables as $monitoredVariable) {
                                $id = $monitoredVariable['ID'];
                                if ($id != 0 && IPS_ObjectExists($id)) {
                                    if ($monitoredVariable['InternalStatus'] == $key) {
                                        $string .= '<tr><td>' . $monitoredVariable['UserDefinedStatus'] . '</td><td>' . $monitoredVariable['Name'] . '</td><td>' . $monitoredVariable['Comment'] . '</td><td>' . $monitoredVariable['BatteryType'] . '</td><td>' . $id . '</td><td>' . $monitoredVariable['LastBatteryReplacement'] . '</td></tr>';
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
                }
                //Remove the last spacer
                if ($data > 0) {
                    $string = substr($string, 0, strrpos($string, '<tr><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td><td>&#8205;</td></tr>'));
                }
            }
            $string .= '</table>';
        }
        $this->SetValue('BatteryList', $string);
    }

    private function GetStatusTextFromInternalStatus(int $InternalStatus): string
    {
        switch ($InternalStatus) {
            case 1:
                return 'LowBattery';

            case 2:
                return 'EmptyBattery';

            default:
                return 'BatteryOK';
        }
    }
}