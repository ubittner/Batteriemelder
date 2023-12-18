<?php

/**
 * @project       Batteriemelder/Batteriemelder/helper/
 * @file          BATM_Reports.php
 * @author        Ulrich Bittner
 * @copyright     2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection SpellCheckingInspection */
/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait BATM_Reports
{
    ##### Notification status

    /**
     * Gets the actual status of the immediate notification.
     *
     * @return void
     * @throws Exception
     */
    public function GetImmediateNotificationStatus(): void
    {
        $this->UpdateFormField('ImmediateNotificationLowBatteryConfigurationButton', 'visible', false);
        $this->UpdateFormField('ImmediateNotificationNormalConfigurationButton', 'visible', false);
        //Low battery
        $lowBatteryVariables = [];
        $criticalVariables = json_decode($this->ReadAttributeString('ImmediateNotificationListDeviceStatusLowBattery'), true);
        $amountLowBatteryVariables = count($criticalVariables) + 1;
        $this->UpdateFormField('ImmediateNotificationListDeviceStatusLowBattery', 'rowCount', $amountLowBatteryVariables);
        foreach ($criticalVariables as $criticalVariable) {
            $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
            foreach ($variables as $variable) {
                $id = 0;
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        }
                    }
                }
                if ($criticalVariable['ID'] == $id) {
                    $batteryType = $variable['BatteryType'];
                    if ($batteryType == '') {
                        $batteryType = $variable['UserDefinedBatteryType'];
                    }
                    $lowBatteryVariables[] = [
                        'ID'          => $criticalVariable['ID'],
                        'Name'        => $variable['Designation'],
                        'Comment'     => $variable['Comment'],
                        'BatteryType' => $batteryType,
                        'Timestamp'   => $criticalVariable['Timestamp'],
                        'rowColor'    => '#FFFFC0']; //yellow
                }
            }
        }
        $this->UpdateFormField('ImmediateNotificationListDeviceStatusLowBattery', 'values', json_encode($lowBatteryVariables));
        //Normal battery
        $normalBatteryVariables = [];
        $criticalVariables = json_decode($this->ReadAttributeString('ImmediateNotificationListDeviceStatusNormal'), true);
        $amountNormalBatteryVariables = count($criticalVariables) + 1;
        $this->UpdateFormField('ImmediateNotificationListDeviceStatusNormal', 'rowCount', $amountNormalBatteryVariables);
        foreach ($criticalVariables as $criticalVariable) {
            $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
            foreach ($variables as $variable) {
                $id = 0;
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        }
                    }
                }
                if ($criticalVariable['ID'] == $id) {
                    $batteryType = $variable['BatteryType'];
                    if ($batteryType == '') {
                        $batteryType = $variable['UserDefinedBatteryType'];
                    }
                    $normalBatteryVariables[] = [
                        'ID'          => $criticalVariable['ID'],
                        'Name'        => $variable['Designation'],
                        'Comment'     => $variable['Comment'],
                        'BatteryType' => $batteryType,
                        'Timestamp'   => $criticalVariable['Timestamp'],
                        'rowColor'    => '#C0FFC0']; //light green
                }
            }
        }
        $this->UpdateFormField('ImmediateNotificationListDeviceStatusNormal', 'values', json_encode($normalBatteryVariables));
    }

    /**
     * Gets the actual status of the daily notification.
     *
     * @return void
     * @throws Exception
     */
    public function GetDailyNotificationStatus(): void
    {
        $this->UpdateFormField('DailyNotificationLowBatteryConfigurationButton', 'visible', false);
        $lowBatteryVariables = [];
        $criticalVariables = json_decode($this->ReadAttributeString('DailyNotificationListDeviceStatusLowBattery'), true);
        $amountLowBatteryVariables = count($criticalVariables) + 1;
        $this->UpdateFormField('DailyNotificationListDeviceStatusLowBattery', 'rowCount', $amountLowBatteryVariables);
        foreach ($criticalVariables as $criticalVariable) {
            $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
            foreach ($variables as $variable) {
                $id = 0;
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        }
                    }
                }
                if ($criticalVariable['ID'] == $id) {
                    $batteryType = $variable['BatteryType'];
                    if ($batteryType == '') {
                        $batteryType = $variable['UserDefinedBatteryType'];
                    }
                    $lowBatteryVariables[] = [
                        'ID'          => $criticalVariable['ID'],
                        'Name'        => $variable['Designation'],
                        'Comment'     => $variable['Comment'],
                        'BatteryType' => $batteryType,
                        'Timestamp'   => $criticalVariable['Timestamp'],
                        'rowColor'    => '#FFFFC0']; //yellow
                }
            }
        }
        $this->UpdateFormField('DailyNotificationListDeviceStatusLowBattery', 'values', json_encode($lowBatteryVariables));
    }

    /**
     * Gets the actual status of the weekly notification.
     *
     * @return void
     * @throws Exception
     */
    public function GetWeeklyNotificationStatus(): void
    {
        $this->UpdateFormField('WeeklyNotificationLowBatteryConfigurationButton', 'visible', false);
        $lowBatteryVariables = [];
        $criticalVariables = json_decode($this->ReadAttributeString('WeeklyNotificationListDeviceStatusLowBattery'), true);
        $amountLowBatteryVariables = count($criticalVariables) + 1;
        $this->UpdateFormField('WeeklyNotificationListDeviceStatusLowBattery', 'rowCount', $amountLowBatteryVariables);
        foreach ($criticalVariables as $criticalVariable) {
            $variables = json_decode($this->ReadPropertyString('TriggerList'), true);
            foreach ($variables as $variable) {
                $id = 0;
                if ($variable['PrimaryCondition'] != '') {
                    $primaryCondition = json_decode($variable['PrimaryCondition'], true);
                    if (array_key_exists(0, $primaryCondition)) {
                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                            $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                        }
                    }
                }
                if ($criticalVariable['ID'] == $id) {
                    $batteryType = $variable['BatteryType'];
                    if ($batteryType == '') {
                        $batteryType = $variable['UserDefinedBatteryType'];
                    }
                    $lowBatteryVariables[] = [
                        'ID'          => $criticalVariable['ID'],
                        'Name'        => $variable['Designation'],
                        'Comment'     => $variable['Comment'],
                        'BatteryType' => $batteryType,
                        'Timestamp'   => $criticalVariable['Timestamp'],
                        'rowColor'    => '#FFFFC0']; //yellow
                }
            }
        }
        $this->UpdateFormField('WeeklyNotificationListDeviceStatusLowBattery', 'values', json_encode($lowBatteryVariables));
    }

    ########### Daily notification

    /**
     * Executes the daily notification.
     *
     * @param bool $CheckDay
     * false =  don't check the day
     * true =   check the day
     *
     * @param bool $ResetCriticalVariables
     * false =  don't reset
     * true =   reset critical variables
     *
     * @return void
     * @throws Exception
     */
    public function ExecuteDailyNotification(bool $CheckDay, bool $ResetCriticalVariables): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $this->SetTimerInterval('DailyNotification', $this->GetInterval('DailyNotificationTime'));
        $timeStamp = date('d.m.Y, H:i:s');
        $delete = false;
        if ($this->GetValue('Active')) {
            $execute = true;
            if ($CheckDay) {
                $execute = false;
                //Check weekday
                $weekday = date('w');
                switch ($weekday) {
                    case 0: //Sunday
                        if ($this->ReadPropertyBoolean('DailyNotificationSunday')) {
                            $execute = true;
                        }
                        break;

                    case 1: //Monday
                        if ($this->ReadPropertyBoolean('DailyNotificationMonday')) {
                            $execute = true;
                        }
                        break;

                    case 2: //Tuesday
                        if ($this->ReadPropertyBoolean('DailyNotificationTuesday')) {
                            $execute = true;
                        }
                        break;

                    case 3: //Wednesday
                        if ($this->ReadPropertyBoolean('DailyNotificationWednesday')) {
                            $execute = true;
                        }
                        break;

                    case 4: //Thursday
                        if ($this->ReadPropertyBoolean('DailyNotificationThursday')) {
                            $execute = true;
                        }
                        break;

                    case 5: //Friday
                        if ($this->ReadPropertyBoolean('DailyNotificationFriday')) {
                            $execute = true;
                        }
                        break;

                    case 6: //Saturday
                        if ($this->ReadPropertyBoolean('DailyNotificationSaturday')) {
                            $execute = true;
                        }
                        break;

                }
            }
            if ($execute) {
                $this->SendDebug(__FUNCTION__, 'Tagesbericht wird versendet...', 0);
                $monitoredVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
                array_multisort(array_column($monitoredVariables, 'Designation'), SORT_ASC, $monitoredVariables);

                ##### Notification

                foreach (json_decode($this->ReadPropertyString('DailyNotification'), true) as $notification) {
                    if (!$notification['Use']) {
                        continue;
                    }
                    $notificationID = $notification['ID'];
                    if ($notificationID <= 1 || @!IPS_ObjectExists($notificationID)) {
                        continue;
                    }
                    //Low battery
                    if ($notification['UseLowBattery']) {
                        foreach (json_decode($this->ReadAttributeString('DailyNotificationListDeviceStatusLowBattery'), true) as $criticalVariable) {
                            $id = $criticalVariable['ID'];
                            foreach ($monitoredVariables as $monitoredVariable) {
                                if ($monitoredVariable['PrimaryCondition'] != '') {
                                    $primaryCondition = json_decode($monitoredVariable['PrimaryCondition'], true);
                                    if (array_key_exists(0, $primaryCondition)) {
                                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                                            $monitoredVariableID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                                            if ($monitoredVariableID == $id) {
                                                $text = $notification['LowBatteryMessageText'];
                                                //Check for placeholder
                                                if (strpos($text, '%1$s') !== false) {
                                                    $text = sprintf($text, $monitoredVariable['Designation']);
                                                }
                                                //Battery type
                                                $batteryType = $monitoredVariable['BatteryType'];
                                                if ($batteryType == '') {
                                                    $batteryType = $monitoredVariable['UserDefinedBatteryType'];
                                                }
                                                if ($notification['UseLowBatteryBatteryType']) {
                                                    if ($batteryType != '') {
                                                        $text = $text . ', ' . $batteryType;
                                                    }
                                                }
                                                //Timestamp
                                                if ($notification['UseLowBatteryTimestamp']) {
                                                    $text = $text . ', ' . $criticalVariable['Timestamp'];
                                                }
                                                $scriptText = 'WFC_SendNotification(' . $notificationID . ', "' . $notification['LowBatteryTitle'] . '", "' . $text . '", "' . $notification['LowBatteryIcon'] . '", ' . $notification['LowBatteryDisplayDuration'] . ');';
                                                @IPS_RunScriptText($scriptText);
                                                IPS_Sleep(100);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    //Battery OK
                    if ($notification['UseBatteryOK']) {
                        foreach ($monitoredVariables as $monitoredVariable) {
                            if (!$monitoredVariable['Use']) {
                                continue;
                            }
                            $id = 0;
                            if ($monitoredVariable['PrimaryCondition'] != '') {
                                $primaryCondition = json_decode($monitoredVariable['PrimaryCondition'], true);
                                if (array_key_exists(0, $primaryCondition)) {
                                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                                        $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                                    }
                                }
                            }
                            if ($id > 1 && @IPS_ObjectExists($id)) {
                                if (in_array($id, array_column(json_decode($this->ReadAttributeString('DailyNotificationListDeviceStatusLowBattery'), true), 'ID'))) {
                                    continue;
                                }
                                $text = $notification['BatteryOKMessageText'];
                                //Check for placeholder
                                if (strpos($text, '%1$s') !== false) {
                                    $text = sprintf($text, $monitoredVariable['Designation']);
                                }
                                if ($notification['UseBatteryOKTimestamp']) {
                                    $text = $text . ', ' . date('d.m.Y, H:i:s');
                                }
                                $scriptText = 'WFC_SendNotification(' . $notificationID . ', "' . $notification['BatteryOKTitle'] . '", "' . $text . '", "' . $notification['BatteryOKIcon'] . '", ' . $notification['BatteryOKDisplayDuration'] . ');';
                                @IPS_RunScriptText($scriptText);
                                IPS_Sleep(100);
                            }
                        }
                    }
                }

                ##### Push notification

                foreach (json_decode($this->ReadPropertyString('DailyPushNotification'), true) as $pushNotification) {
                    if (!$pushNotification['Use']) {
                        continue;
                    }
                    $pushNotificationID = $pushNotification['ID'];
                    if ($pushNotificationID <= 1 || @!IPS_ObjectExists($pushNotificationID)) {
                        continue;
                    }
                    //Low battery
                    if ($pushNotification['UseLowBattery']) {
                        foreach (json_decode($this->ReadAttributeString('DailyNotificationListDeviceStatusLowBattery'), true) as $criticalVariable) {
                            $id = $criticalVariable['ID'];
                            foreach ($monitoredVariables as $monitoredVariable) {
                                if ($monitoredVariable['PrimaryCondition'] != '') {
                                    $primaryCondition = json_decode($monitoredVariable['PrimaryCondition'], true);
                                    if (array_key_exists(0, $primaryCondition)) {
                                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                                            $monitoredVariableID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                                            if ($monitoredVariableID == $id) {
                                                //Title length max 32 characters
                                                $title = substr($pushNotification['LowBatteryTitle'], 0, 32);
                                                $text = "\n" . $pushNotification['LowBatteryMessageText'];
                                                //Check for placeholder
                                                if (strpos($text, '%1$s') !== false) {
                                                    $text = sprintf($text, $monitoredVariable['Designation']);
                                                }
                                                //Battery type
                                                $batteryType = $monitoredVariable['BatteryType'];
                                                if ($batteryType == '') {
                                                    $batteryType = $monitoredVariable['UserDefinedBatteryType'];
                                                }
                                                if ($pushNotification['UseLowBatteryBatteryType']) {
                                                    if ($batteryType != '') {
                                                        $text = $text . ', ' . $batteryType;
                                                    }
                                                }
                                                //Timestamp
                                                if ($pushNotification['UseLowBatteryTimestamp']) {
                                                    $text = $text . ', ' . $criticalVariable['Timestamp'];
                                                }
                                                //Text length max 256 characters
                                                $text = substr($text, 0, 256);
                                                $scriptText = 'WFC_PushNotification(' . $pushNotificationID . ', "' . $title . '", "' . $text . '", "' . $pushNotification['LowBatterySound'] . '", ' . $pushNotification['LowBatteryTargetID'] . ');';
                                                @IPS_RunScriptText($scriptText);
                                                IPS_Sleep(100);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    //Battery OK
                    if ($pushNotification['UseBatteryOK']) {
                        foreach ($monitoredVariables as $monitoredVariable) {
                            if (!$monitoredVariable['Use']) {
                                continue;
                            }
                            $id = 0;
                            if ($monitoredVariable['PrimaryCondition'] != '') {
                                $primaryCondition = json_decode($monitoredVariable['PrimaryCondition'], true);
                                if (array_key_exists(0, $primaryCondition)) {
                                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                                        $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                                    }
                                }
                            }
                            if ($id > 1 && @IPS_ObjectExists($id)) {
                                if (in_array($id, array_column(json_decode($this->ReadAttributeString('DailyNotificationListDeviceStatusLowBattery'), true), 'ID'))) {
                                    continue;
                                }
                                //Title length max 32 characters
                                $title = substr($pushNotification['BatteryOKTitle'], 0, 32);
                                $text = "\n" . $pushNotification['BatteryOKMessageText'];
                                //Check for placeholder
                                if (strpos($text, '%1$s') !== false) {
                                    $text = sprintf($text, $monitoredVariable['Designation']);
                                }
                                if ($pushNotification['UseBatteryOKTimestamp']) {
                                    $text = $text . ', ' . date('d.m.Y, H:i:s');
                                }
                                //Text length max 256 characters
                                $text = substr($text, 0, 256);
                                $scriptText = 'WFC_PushNotification(' . $pushNotificationID . ', "' . $title . '", "' . $text . '", "' . $pushNotification['BatteryOKSound'] . '", ' . $pushNotification['BatteryOKTargetID'] . ');';
                                @IPS_RunScriptText($scriptText);
                                IPS_Sleep(100);
                            }
                        }
                    }
                }

                ##### Email notification

                foreach (json_decode($this->ReadPropertyString('DailyMailerNotification'), true) as $mailer) {
                    $mailerID = $mailer['ID'];
                    if ($mailerID <= 1 || @!IPS_ObjectExists($mailerID)) {
                        continue;
                    }
                    if (!$mailer['Use']) {
                        continue;
                    }
                    //Check if we have more than one message category
                    $multiMessage = 0;
                    //Check low battery
                    $useLowBattery = false;
                    if ($mailer['UseLowBattery']) {
                        $useLowBattery = true;
                        $multiMessage++;
                    }
                    //Check for battery ok
                    $useBatteryOK = false;
                    if ($mailer['UseBatteryOK']) {
                        $useBatteryOK = true;
                        $multiMessage++;
                    }
                    //Create message block for low battery
                    $existing = false;
                    $lowBatteryMessageText = "Batterie schwach:\n\n";
                    foreach (json_decode($this->ReadAttributeString('DailyNotificationListDeviceStatusLowBattery'), true) as $criticalVariable) {
                        $id = $criticalVariable['ID'];
                        $existing = true;
                        foreach ($monitoredVariables as $monitoredVariable) {
                            if ($monitoredVariable['PrimaryCondition'] != '') {
                                $primaryCondition = json_decode($monitoredVariable['PrimaryCondition'], true);
                                if (array_key_exists(0, $primaryCondition)) {
                                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                                        $monitoredVariableID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                                        if ($monitoredVariableID == $id) {
                                            //Message text
                                            $lineText = $mailer['LowBatteryMessageText'];
                                            $name = $monitoredVariable['Designation'];
                                            if ($monitoredVariable['Comment'] != '') {
                                                $name = $name . ', ' . $monitoredVariable['Comment'];
                                            }
                                            //Check for placeholder
                                            if (strpos($lineText, '%1$s') !== false) {
                                                $lineText = sprintf($lineText, $name);
                                            }
                                            //Timestamp
                                            if ($mailer['UseLowBatteryTimestamp']) {
                                                $lineText = $lineText . ', ' . $criticalVariable['Timestamp'];
                                            }
                                            //Variable ID
                                            if ($mailer['UseLowBatteryVariableID']) {
                                                $lineText = $lineText . ', ID: ' . $id;
                                            }
                                            //Battery type
                                            $batteryType = $monitoredVariable['BatteryType'];
                                            if ($batteryType == '') {
                                                $batteryType = $monitoredVariable['UserDefinedBatteryType'];
                                            }
                                            if ($mailer['UseLowBatteryBatteryType']) {
                                                if ($batteryType != '') {
                                                    $lineText = $lineText . ', Batterietyp: ' . $batteryType;
                                                }
                                            }
                                            $lowBatteryMessageText .= $lineText . "\n";
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if (!$existing) {
                        $lowBatteryMessageText .= 'Keine';
                    }
                    $lowBatteryMessageText .= "\n\n\n\n";
                    //Create message block for battery ok
                    $existing = false;
                    $batteryOKMessageText = "Batterie OK:\n\n";
                    foreach ($monitoredVariables as $monitoredVariable) {
                        if (!$monitoredVariable['Use']) {
                            continue;
                        }
                        $id = 0;
                        if ($monitoredVariable['PrimaryCondition'] != '') {
                            $primaryCondition = json_decode($monitoredVariable['PrimaryCondition'], true);
                            if (array_key_exists(0, $primaryCondition)) {
                                if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                                    $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                                }
                            }
                        }
                        if ($id > 1 && @IPS_ObjectExists($id)) {
                            if (in_array($id, array_column(json_decode($this->ReadAttributeString('DailyNotificationListDeviceStatusLowBattery'), true), 'ID'))) {
                                continue;
                            }
                            $existing = true;
                            //Message text
                            $lineText = $mailer['BatteryOKMessageText'];
                            $name = $monitoredVariable['Designation'];
                            if ($monitoredVariable['Comment'] != '') {
                                $name = $name . ', ' . $monitoredVariable['Comment'];
                            }
                            //Check for placeholder
                            if (strpos($lineText, '%1$s') !== false) {
                                $lineText = sprintf($lineText, $name);
                            }
                            //Timestamp
                            if ($mailer['UseBatteryOKTimestamp']) {
                                $lineText = $lineText . ', ' . date('d.m.Y, H:i:s');
                            }
                            //Variable ID
                            if ($mailer['UseBatteryOKVariableID']) {
                                $lineText = $lineText . ', ID: ' . $id;
                            }
                            //Battery type
                            $batteryType = $monitoredVariable['BatteryType'];
                            if ($batteryType == '') {
                                $batteryType = $monitoredVariable['UserDefinedBatteryType'];
                            }
                            if ($mailer['UseBatteryOKBatteryType']) {
                                if ($batteryType != '') {
                                    $lineText = $lineText . ', Batterietyp: ' . $batteryType;
                                }
                            }
                            $batteryOKMessageText .= $lineText . "\n";
                        }
                    }
                    if (!$existing) {
                        $batteryOKMessageText .= 'Keine';
                    }
                    $batteryOKMessageText .= "\n\n\n\n";
                    //Message block header
                    $messageText = 'Tagesbericht vom ' . $timeStamp . ":\n\n\n";
                    $sendEmail = false;
                    //We only have one category
                    if ($multiMessage == 1) {
                        if ($useLowBattery) {
                            if (strpos($lowBatteryMessageText, 'Keine') === false) {
                                $sendEmail = true;
                                $messageText .= $lowBatteryMessageText;
                            }
                        }
                        if ($useBatteryOK) {
                            if (strpos($batteryOKMessageText, 'Keine') === false) {
                                $sendEmail = true;
                                $messageText .= $batteryOKMessageText;
                            }
                        }
                    }
                    //We have more than one category
                    if ($multiMessage > 1) {
                        $sendEmail = false;
                        if ($useLowBattery) {
                            if (strpos($lowBatteryMessageText, 'Keine') === false) {
                                $sendEmail = true;
                                $messageText .= $lowBatteryMessageText;
                            }
                        }
                        if ($useBatteryOK) {
                            if (strpos($batteryOKMessageText, 'Keine') === false) {
                                $sendEmail = true;
                                $messageText .= $batteryOKMessageText;
                            }
                        }
                    }
                    //Debug
                    $this->SendDebug(__FUNCTION__, 'E-Mail Versand: ' . json_encode($sendEmail), 0);
                    //Send email
                    if ($sendEmail) {
                        $scriptText = 'MA_SendMessage(' . $mailerID . ', "' . $mailer['Subject'] . '", "' . $messageText . '");';
                        @IPS_RunScriptText($scriptText);
                    }
                }
                if ($ResetCriticalVariables) {
                    $delete = true;
                }
            }
        }
        //Reset critical variables
        if ($this->ReadPropertyBoolean('DailyNotificationAlwaysResetCriticalVariables') || $delete) {
            $this->ResetAttribute('DailyNotificationListDeviceStatusLowBattery');
        }
    }

    ########### Weekly notification

    /**
     * Executes the weekly notification.
     *
     * @param bool $CheckDay
     * false =  don't check the day
     * true =   check the day
     *
     * @param bool $ResetCriticalVariables
     * false =  don't reset
     * true =   reset critical variables
     *
     * @return void
     * @throws Exception
     */
    public function ExecuteWeeklyNotification(bool $CheckDay, bool $ResetCriticalVariables): void
    {
        $this->SendDebug(__FUNCTION__, 'wird ausgeführt', 0);
        $checkDayText = 'nein';
        if ($CheckDay) {
            $checkDayText = 'ja';
        }
        $this->SendDebug(__FUNCTION__, 'Tagesprüfung: ' . $checkDayText, 0);
        $resetCriticalVariablesText = 'nein';
        if ($ResetCriticalVariables) {
            $resetCriticalVariablesText = 'ja';
        }
        $this->SendDebug(__FUNCTION__, 'Kritische Variablen zurücksetzen: ' . $resetCriticalVariablesText, 0);
        $this->SetTimerInterval('WeeklyNotification', $this->GetInterval('WeeklyNotificationTime'));
        $timeStamp = date('d.m.Y, H:i:s');
        if ($this->GetValue('Active')) {
            $this->SendDebug(__FUNCTION__, 'Aktiv: ja', 0);
            $execute = true;
            if ($CheckDay) {
                $execute = false;
                //Check weekday
                $weekday = date('w');
                if ($weekday == $this->ReadPropertyInteger('WeeklyNotificationDay')) {
                    $execute = true;
                }
            }
            if ($execute) {
                $this->SendDebug(__FUNCTION__, 'Wochenbericht wird versendet...', 0);

                $monitoredVariables = json_decode($this->ReadPropertyString('TriggerList'), true);
                array_multisort(array_column($monitoredVariables, 'Designation'), SORT_ASC, $monitoredVariables);

                ##### Notification

                foreach (json_decode($this->ReadPropertyString('WeeklyNotification'), true) as $notification) {
                    if (!$notification['Use']) {
                        continue;
                    }
                    $notificationID = $notification['ID'];
                    if ($notificationID <= 1 || @!IPS_ObjectExists($notificationID)) {
                        continue;
                    }
                    //Low battery
                    if ($notification['UseLowBattery']) {
                        foreach (json_decode($this->ReadAttributeString('WeeklyNotificationListDeviceStatusLowBattery'), true) as $criticalVariable) {
                            $id = $criticalVariable['ID'];
                            foreach ($monitoredVariables as $monitoredVariable) {
                                if ($monitoredVariable['PrimaryCondition'] != '') {
                                    $primaryCondition = json_decode($monitoredVariable['PrimaryCondition'], true);
                                    if (array_key_exists(0, $primaryCondition)) {
                                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                                            $monitoredVariableID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                                            if ($monitoredVariableID == $id) {
                                                $text = $notification['LowBatteryMessageText'];
                                                //Check for placeholder
                                                if (strpos($text, '%1$s') !== false) {
                                                    $text = sprintf($text, $monitoredVariable['Designation']);
                                                }
                                                //Battery type
                                                $batteryType = $monitoredVariable['BatteryType'];
                                                if ($batteryType == '') {
                                                    $batteryType = $monitoredVariable['UserDefinedBatteryType'];
                                                }
                                                if ($notification['UseLowBatteryBatteryType']) {
                                                    if ($batteryType != '') {
                                                        $text = $text . ', ' . $batteryType;
                                                    }
                                                }
                                                //Timestamp
                                                if ($notification['UseLowBatteryTimestamp']) {
                                                    $text = $text . ', ' . $criticalVariable['Timestamp'];
                                                }
                                                $scriptText = 'WFC_SendNotification(' . $notificationID . ', "' . $notification['LowBatteryTitle'] . '", "' . $text . '", "' . $notification['LowBatteryIcon'] . '", ' . $notification['LowBatteryDisplayDuration'] . ');';
                                                @IPS_RunScriptText($scriptText);
                                                IPS_Sleep(100);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    //Battery OK
                    if ($notification['UseBatteryOK']) {
                        foreach ($monitoredVariables as $monitoredVariable) {
                            if (!$monitoredVariable['Use']) {
                                continue;
                            }
                            $id = 0;
                            if ($monitoredVariable['PrimaryCondition'] != '') {
                                $primaryCondition = json_decode($monitoredVariable['PrimaryCondition'], true);
                                if (array_key_exists(0, $primaryCondition)) {
                                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                                        $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                                    }
                                }
                            }
                            if ($id > 1 && @IPS_ObjectExists($id)) {
                                if (in_array($id, array_column(json_decode($this->ReadAttributeString('WeeklyNotificationListDeviceStatusLowBattery'), true), 'ID'))) {
                                    continue;
                                }
                                $text = $notification['BatteryOKMessageText'];
                                //Check for placeholder
                                if (strpos($text, '%1$s') !== false) {
                                    $text = sprintf($text, $monitoredVariable['Designation']);
                                }
                                if ($notification['UseBatteryOKTimestamp']) {
                                    $text = $text . ', ' . date('d.m.Y, H:i:s');
                                }
                                $scriptText = 'WFC_SendNotification(' . $notificationID . ', "' . $notification['BatteryOKTitle'] . '", "' . $text . '", "' . $notification['BatteryOKIcon'] . '", ' . $notification['BatteryOKDisplayDuration'] . ');';
                                @IPS_RunScriptText($scriptText);
                                IPS_Sleep(100);
                            }
                        }
                    }
                }

                ##### Push notification

                foreach (json_decode($this->ReadPropertyString('WeeklyPushNotification'), true) as $pushNotification) {
                    if (!$pushNotification['Use']) {
                        continue;
                    }
                    $pushNotificationID = $pushNotification['ID'];
                    if ($pushNotificationID <= 1 || @!IPS_ObjectExists($pushNotificationID)) {
                        continue;
                    }
                    //Low battery
                    if ($pushNotification['UseLowBattery']) {
                        foreach (json_decode($this->ReadAttributeString('WeeklyNotificationListDeviceStatusLowBattery'), true) as $criticalVariable) {
                            $id = $criticalVariable['ID'];
                            foreach ($monitoredVariables as $monitoredVariable) {
                                if ($monitoredVariable['PrimaryCondition'] != '') {
                                    $primaryCondition = json_decode($monitoredVariable['PrimaryCondition'], true);
                                    if (array_key_exists(0, $primaryCondition)) {
                                        if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                                            $monitoredVariableID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                                            if ($monitoredVariableID == $id) {
                                                //Title length max 32 characters
                                                $title = substr($pushNotification['LowBatteryTitle'], 0, 32);
                                                $text = "\n" . $pushNotification['LowBatteryMessageText'];
                                                //Check for placeholder
                                                if (strpos($text, '%1$s') !== false) {
                                                    $text = sprintf($text, $monitoredVariable['Designation']);
                                                }
                                                //Battery type
                                                $batteryType = $monitoredVariable['BatteryType'];
                                                if ($batteryType == '') {
                                                    $batteryType = $monitoredVariable['UserDefinedBatteryType'];
                                                }
                                                if ($pushNotification['UseLowBatteryBatteryType']) {
                                                    if ($batteryType != '') {
                                                        $text = $text . ', ' . $batteryType;
                                                    }
                                                }
                                                //Timestamp
                                                if ($pushNotification['UseLowBatteryTimestamp']) {
                                                    $text = $text . ', ' . $criticalVariable['Timestamp'];
                                                }
                                                //Text length max 256 characters
                                                $text = substr($text, 0, 256);
                                                $scriptText = 'WFC_PushNotification(' . $pushNotificationID . ', "' . $title . '", "' . $text . '", "' . $pushNotification['LowBatterySound'] . '", ' . $pushNotification['LowBatteryTargetID'] . ');';
                                                @IPS_RunScriptText($scriptText);
                                                IPS_Sleep(100);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    //Battery OK
                    if ($pushNotification['UseBatteryOK']) {
                        foreach ($monitoredVariables as $monitoredVariable) {
                            if (!$monitoredVariable['Use']) {
                                continue;
                            }
                            $id = 0;
                            if ($monitoredVariable['PrimaryCondition'] != '') {
                                $primaryCondition = json_decode($monitoredVariable['PrimaryCondition'], true);
                                if (array_key_exists(0, $primaryCondition)) {
                                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                                        $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                                    }
                                }
                            }
                            if ($id > 1 && @IPS_ObjectExists($id)) {
                                if (in_array($id, array_column(json_decode($this->ReadAttributeString('WeeklyNotificationListDeviceStatusLowBattery'), true), 'ID'))) {
                                    continue;
                                }
                                //Title length max 32 characters
                                $title = substr($pushNotification['BatteryOKTitle'], 0, 32);
                                $text = "\n" . $pushNotification['BatteryOKMessageText'];
                                //Check for placeholder
                                if (strpos($text, '%1$s') !== false) {
                                    $text = sprintf($text, $monitoredVariable['Designation']);
                                }
                                if ($pushNotification['UseBatteryOKTimestamp']) {
                                    $text = $text . ', ' . date('d.m.Y, H:i:s');
                                }
                                //Text length max 256 characters
                                $text = substr($text, 0, 256);
                                $scriptText = 'WFC_PushNotification(' . $pushNotificationID . ', "' . $title . '", "' . $text . '", "' . $pushNotification['BatteryOKSound'] . '", ' . $pushNotification['BatteryOKTargetID'] . ');';
                                @IPS_RunScriptText($scriptText);
                                IPS_Sleep(100);
                            }
                        }
                    }
                }

                ##### Email notification

                foreach (json_decode($this->ReadPropertyString('WeeklyMailerNotification'), true) as $mailer) {
                    $mailerID = $mailer['ID'];
                    if ($mailerID <= 1 || @!IPS_ObjectExists($mailerID)) {
                        continue;
                    }
                    if (!$mailer['Use']) {
                        continue;
                    }
                    //Check if we have more than one message category
                    $multiMessage = 0;
                    //Check low battery
                    $useLowBattery = false;
                    if ($mailer['UseLowBattery']) {
                        $useLowBattery = true;
                        $multiMessage++;
                    }
                    //Check for battery ok
                    $useBatteryOK = false;
                    if ($mailer['UseBatteryOK']) {
                        $useBatteryOK = true;
                        $multiMessage++;
                    }
                    //Create message block for low battery
                    $existing = false;
                    $lowBatteryMessageText = "Batterie schwach:\n\n";
                    foreach (json_decode($this->ReadAttributeString('WeeklyNotificationListDeviceStatusLowBattery'), true) as $criticalVariable) {
                        $id = $criticalVariable['ID'];
                        $existing = true;
                        foreach ($monitoredVariables as $monitoredVariable) {
                            if ($monitoredVariable['PrimaryCondition'] != '') {
                                $primaryCondition = json_decode($monitoredVariable['PrimaryCondition'], true);
                                if (array_key_exists(0, $primaryCondition)) {
                                    if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                                        $monitoredVariableID = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                                        if ($monitoredVariableID == $id) {
                                            //Message text
                                            $lineText = $mailer['LowBatteryMessageText'];
                                            $name = $monitoredVariable['Designation'];
                                            if ($monitoredVariable['Comment'] != '') {
                                                $name = $name . ', ' . $monitoredVariable['Comment'];
                                            }
                                            //Check for placeholder
                                            if (strpos($lineText, '%1$s') !== false) {
                                                $lineText = sprintf($lineText, $name);
                                            }
                                            //Timestamp
                                            if ($mailer['UseLowBatteryTimestamp']) {
                                                $lineText = $lineText . ', ' . $criticalVariable['Timestamp'];
                                            }
                                            //Variable ID
                                            if ($mailer['UseLowBatteryVariableID']) {
                                                $lineText = $lineText . ', ID: ' . $id;
                                            }
                                            //Battery type
                                            $batteryType = $monitoredVariable['BatteryType'];
                                            if ($batteryType == '') {
                                                $batteryType = $monitoredVariable['UserDefinedBatteryType'];
                                            }
                                            if ($mailer['UseLowBatteryBatteryType']) {
                                                if ($batteryType != '') {
                                                    $lineText = $lineText . ', Batterietyp: ' . $batteryType;
                                                }
                                            }
                                            $lowBatteryMessageText .= $lineText . "\n";
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if (!$existing) {
                        $lowBatteryMessageText .= 'Keine';
                    }
                    $lowBatteryMessageText .= "\n\n\n\n";
                    //Create message block for battery ok
                    $existing = false;
                    $batteryOKMessageText = "Batterie OK:\n\n";
                    foreach ($monitoredVariables as $monitoredVariable) {
                        if (!$monitoredVariable['Use']) {
                            continue;
                        }
                        $id = 0;
                        if ($monitoredVariable['PrimaryCondition'] != '') {
                            $primaryCondition = json_decode($monitoredVariable['PrimaryCondition'], true);
                            if (array_key_exists(0, $primaryCondition)) {
                                if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                                    $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                                }
                            }
                        }
                        if ($id > 1 && @IPS_ObjectExists($id)) {
                            if (in_array($id, array_column(json_decode($this->ReadAttributeString('WeeklyNotificationListDeviceStatusLowBattery'), true), 'ID'))) {
                                continue;
                            }
                            $existing = true;
                            //Message text
                            $lineText = $mailer['BatteryOKMessageText'];
                            $name = $monitoredVariable['Designation'];
                            if ($monitoredVariable['Comment'] != '') {
                                $name = $name . ', ' . $monitoredVariable['Comment'];
                            }
                            //Check for placeholder
                            if (strpos($lineText, '%1$s') !== false) {
                                $lineText = sprintf($lineText, $name);
                            }
                            //Timestamp
                            if ($mailer['UseBatteryOKTimestamp']) {
                                $lineText = $lineText . ', ' . date('d.m.Y, H:i:s');
                            }
                            //Variable ID
                            if ($mailer['UseBatteryOKVariableID']) {
                                $lineText = $lineText . ', ID: ' . $id;
                            }
                            //Battery type
                            $batteryType = $monitoredVariable['BatteryType'];
                            if ($batteryType == '') {
                                $batteryType = $monitoredVariable['UserDefinedBatteryType'];
                            }
                            if ($mailer['UseBatteryOKBatteryType']) {
                                if ($batteryType != '') {
                                    $lineText = $lineText . ', Batterietyp: ' . $batteryType;
                                }
                            }
                            $batteryOKMessageText .= $lineText . "\n";
                        }
                    }
                    if (!$existing) {
                        $batteryOKMessageText .= 'Keine';
                    }
                    $batteryOKMessageText .= "\n\n\n\n";
                    //Message block header
                    $messageText = 'Wochenbericht vom ' . $timeStamp . ":\n\n\n";
                    $sendEmail = false;
                    //We only have one category
                    if ($multiMessage == 1) {
                        if ($useLowBattery) {
                            if (strpos($lowBatteryMessageText, 'Keine') === false) {
                                $sendEmail = true;
                                $messageText .= $lowBatteryMessageText;
                            }
                        }
                        if ($useBatteryOK) {
                            if (strpos($batteryOKMessageText, 'Keine') === false) {
                                $sendEmail = true;
                                $messageText .= $batteryOKMessageText;
                            }
                        }
                    }
                    //We have more than one category
                    if ($multiMessage > 1) {
                        $sendEmail = false;
                        if ($useLowBattery) {
                            if (strpos($lowBatteryMessageText, 'Keine') === false) {
                                $sendEmail = true;
                                $messageText .= $lowBatteryMessageText;
                            }
                        }
                        if ($useBatteryOK) {
                            if (strpos($batteryOKMessageText, 'Keine') === false) {
                                $sendEmail = true;
                                $messageText .= $batteryOKMessageText;
                            }
                        }
                    }
                    //Debug
                    $this->SendDebug(__FUNCTION__, 'E-Mail Versand: ' . json_encode($sendEmail), 0);
                    //Send email
                    if ($sendEmail) {
                        $scriptText = 'MA_SendMessage(' . $mailerID . ', "' . $mailer['Subject'] . '", "' . $messageText . '");';
                        @IPS_RunScriptText($scriptText);
                    }
                }
                //Reset critical variables
                if ($ResetCriticalVariables) {
                    $this->ResetAttribute('WeeklyNotificationListDeviceStatusLowBattery');
                }
            }
        }
    }
}