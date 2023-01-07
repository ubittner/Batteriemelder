<?php

/**
 * @project       Batteriemelder/Batteriemelder
 * @file          BATM_DailyNotification.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection DuplicatedCode */
/** @noinspection PhpUnused */

declare(strict_types=1);

trait BATM_Reports
{
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
                //Check the overall status first
                //Normal status
                $overallStatus = 0;
                $overallStatusText = 'OK';
                $overallStatusNotificationName = 'DailyNotificationTotalStatusOK';
                //Update overdue
                if (!empty(json_decode($this->ReadAttributeString('DailyNotificationListDeviceStatusUpdateOverdue'), true))) {
                    $overallStatus = 1;
                }
                //Low battery
                if (!empty(json_decode($this->ReadAttributeString('DailyNotificationListDeviceStatusLowBattery'), true))) {
                    $overallStatus = 1;
                }
                if ($overallStatus == 1) {
                    $overallStatusNotificationName = 'DailyNotificationTotalStatusAlarm';
                    $overallStatusText = 'Alarm';
                }
                $this->SendDebug(__FUNCTION__, 'Gesamtstatus: ' . $overallStatusText, 0);
                $notificationID = $this->ReadPropertyInteger('DailyNotification');
                if ($notificationID > 1 && @IPS_ObjectExists($notificationID)) { //0 = main category, 1 = none
                    $overallStatusNotification = json_decode($this->ReadPropertyString($overallStatusNotificationName), true);
                    if ($overallStatusNotification[0]['Use']) {
                        $messageText = $overallStatusNotification[0]['MessageText'];
                        if ($overallStatusNotification[0]['UseTimestamp']) {
                            $messageText = $messageText . ' ' . $timeStamp;
                        }
                        $this->SendDebug(__FUNCTION__, 'Meldungstext: ' . $messageText, 0);
                        //WebFront notification
                        if ($overallStatusNotification[0]['UseWebFrontNotification']) {
                            @BN_SendWebFrontNotification($notificationID, $overallStatusNotification[0]['WebFrontNotificationTitle'], "\n" . $messageText, $overallStatusNotification[0]['WebFrontNotificationIcon'], $overallStatusNotification[0]['WebFrontNotificationDisplayDuration']);
                        }
                        //WebFront push notification
                        if ($overallStatusNotification[0]['UseWebFrontPushNotification']) {
                            @BN_SendWebFrontPushNotification($notificationID, $overallStatusNotification[0]['WebFrontPushNotificationTitle'], "\n" . $messageText, $overallStatusNotification[0]['WebFrontPushNotificationSound'], $overallStatusNotification[0]['WebFrontPushNotificationTargetID']);
                        }
                        //E-Mail
                        if ($overallStatusNotification[0]['UseMailer']) {
                            @BN_SendMailNotification($notificationID, $overallStatusNotification[0]['Subject'], "\n\n" . $messageText);
                        }
                        //SMS
                        if ($overallStatusNotification[0]['UseSMS']) {
                            @BN_SendNexxtMobileSMS($notificationID, $overallStatusNotification[0]['SMSTitle'], "\n\n" . $messageText);
                            @BN_SendSipgateSMS($notificationID, $overallStatusNotification[0]['SMSTitle'], "\n\n" . $messageText);
                        }
                        //Telegram
                        if ($overallStatusNotification[0]['UseTelegram']) {
                            @BN_SendTelegramMessage($notificationID, $overallStatusNotification[0]['TelegramTitle'], "\n\n" . $messageText);
                        }
                    }
                }

                //Check the device status is next
                //Notification for WebFront, WebFront push notification, SMS and Telegram
                //Update overdue
                $updateOverdueNotification = json_decode($this->ReadPropertyString('DailyNotificationDeviceStatusUpdateOverdue'), true);
                $updateOverdueVariables = json_decode($this->ReadAttributeString('DailyNotificationListDeviceStatusUpdateOverdue'), true);
                foreach ($updateOverdueVariables as $updateOverdueVariable) {
                    if ($updateOverdueNotification[0]['Use']) {
                        $messageText = $updateOverdueNotification[0]['MessageText'];
                        //Check for placeholder
                        if (strpos($messageText, '%1$s') !== false) {
                            $messageText = sprintf($messageText, $updateOverdueVariable['Name']);
                        }
                        if ($updateOverdueNotification[0]['UseTimestamp']) {
                            $messageText = $messageText . ' ' . $updateOverdueVariable['Timestamp'];
                        }
                        //WebFront notification
                        if ($updateOverdueNotification[0]['UseWebFrontNotification']) {
                            @BN_SendWebFrontNotification($notificationID, $updateOverdueNotification[0]['WebFrontNotificationTitle'], "\n" . $messageText, $updateOverdueNotification[0]['WebFrontNotificationIcon'], $updateOverdueNotification[0]['WebFrontNotificationDisplayDuration']);
                        }
                        //WebFront push notification
                        if ($updateOverdueNotification[0]['UseWebFrontPushNotification']) {
                            @BN_SendWebFrontPushNotification($notificationID, $updateOverdueNotification[0]['WebFrontPushNotificationTitle'], "\n" . $messageText, $updateOverdueNotification[0]['WebFrontPushNotificationSound'], $updateOverdueNotification[0]['WebFrontPushNotificationTargetID']);
                        }
                        //SMS
                        if ($updateOverdueNotification[0]['UseSMS']) {
                            @BN_SendNexxtMobileSMS($notificationID, $updateOverdueNotification[0]['SMSTitle'], "\n\n" . $messageText);
                            @BN_SendSipgateSMS($notificationID, $updateOverdueNotification[0]['SMSTitle'], "\n\n" . $messageText);
                        }
                        //Telegram
                        if ($updateOverdueNotification[0]['UseTelegram']) {
                            @BN_SendTelegramMessage($notificationID, $updateOverdueNotification[0]['TelegramTitle'], "\n\n" . $messageText);
                        }
                    }
                }
                //Low battery
                $lowBatteryNotification = json_decode($this->ReadPropertyString('DailyNotificationDeviceStatusLowBattery'), true);
                $lowBatteryVariables = json_decode($this->ReadAttributeString('DailyNotificationListDeviceStatusLowBattery'), true);
                foreach ($lowBatteryVariables as $lowBatteryVariable) {
                    if ($lowBatteryNotification[0]['Use']) {
                        $messageText = $lowBatteryNotification[0]['MessageText'];
                        //Check for placeholder
                        if (strpos($messageText, '%1$s') !== false) {
                            $messageText = sprintf($messageText, $lowBatteryVariable['Name']);
                        }
                        if ($lowBatteryNotification[0]['UseTimestamp']) {
                            $messageText = $messageText . ' ' . $lowBatteryVariable['Timestamp'];
                        }
                        //WebFront notification
                        if ($lowBatteryNotification[0]['UseWebFrontNotification']) {
                            @BN_SendWebFrontNotification($notificationID, $lowBatteryNotification[0]['WebFrontNotificationTitle'], "\n" . $messageText, $lowBatteryNotification[0]['WebFrontNotificationIcon'], $lowBatteryNotification[0]['WebFrontNotificationDisplayDuration']);
                        }
                        //WebFront push notification
                        if ($lowBatteryNotification[0]['UseWebFrontPushNotification']) {
                            @BN_SendWebFrontPushNotification($notificationID, $lowBatteryNotification[0]['WebFrontPushNotificationTitle'], "\n" . $messageText, $lowBatteryNotification[0]['WebFrontPushNotificationSound'], $lowBatteryNotification[0]['WebFrontPushNotificationTargetID']);
                        }
                        //SMS
                        if ($lowBatteryNotification[0]['UseSMS']) {
                            @BN_SendNexxtMobileSMS($notificationID, $lowBatteryNotification[0]['SMSTitle'], "\n\n" . $messageText);
                            @BN_SendSipgateSMS($notificationID, $lowBatteryNotification[0]['SMSTitle'], "\n\n" . $messageText);
                        }
                        //Telegram
                        if ($lowBatteryNotification[0]['UseTelegram']) {
                            @BN_SendTelegramMessage($notificationID, $lowBatteryNotification[0]['TelegramTitle'], "\n\n" . $messageText);
                        }
                    }
                }
                //Normal status
                $normalStatusNotification = json_decode($this->ReadPropertyString('DailyNotificationDeviceStatusOK'), true);
                $normalStatusVariables = json_decode($this->GetMonitoredVariables(), true);
                foreach ($normalStatusVariables as $normalStatusVariable) {
                    $variableID = $normalStatusVariable['ID'];
                    if ($variableID > 1 && @IPS_ObjectExists($variableID)) {
                        if (in_array($variableID, array_column(json_decode($this->ReadAttributeString('DailyNotificationListDeviceStatusUpdateOverdue'), true), 'ID'))) {
                            continue;
                        }
                        if (in_array($variableID, array_column(json_decode($this->ReadAttributeString('DailyNotificationListDeviceStatusLowBattery'), true), 'ID'))) {
                            continue;
                        }
                        if ($normalStatusNotification[0]['Use']) {
                            $messageText = $normalStatusNotification[0]['MessageText'];
                            //Check for placeholder
                            if (strpos($messageText, '%1$s') !== false) {
                                $messageText = sprintf($messageText, $normalStatusVariable['Name']);
                            }
                            if ($normalStatusNotification[0]['UseTimestamp']) {
                                $messageText = $messageText . ' ' . $timeStamp;
                            }
                            //WebFront notification
                            if ($normalStatusNotification[0]['UseWebFrontNotification']) {
                                @BN_SendWebFrontNotification($notificationID, $normalStatusNotification[0]['WebFrontNotificationTitle'], "\n" . $messageText, $normalStatusNotification[0]['WebFrontNotificationIcon'], $normalStatusNotification[0]['WebFrontNotificationDisplayDuration']);
                            }
                            //WebFront push notification
                            if ($normalStatusNotification[0]['UseWebFrontPushNotification']) {
                                @BN_SendWebFrontPushNotification($notificationID, $normalStatusNotification[0]['WebFrontPushNotificationTitle'], "\n" . $messageText, $normalStatusNotification[0]['WebFrontPushNotificationSound'], $normalStatusNotification[0]['WebFrontPushNotificationTargetID']);
                            }
                            //SMS
                            if ($normalStatusNotification[0]['UseSMS']) {
                                @BN_SendNexxtMobileSMS($notificationID, $normalStatusNotification[0]['SMSTitle'], "\n\n" . $messageText);
                                @BN_SendSipgateSMS($notificationID, $normalStatusNotification[0]['SMSTitle'], "\n\n" . $messageText);
                            }
                            //Telegram
                            if ($normalStatusNotification[0]['UseTelegram']) {
                                @BN_SendTelegramMessage($notificationID, $normalStatusNotification[0]['TelegramTitle'], "\n\n" . $messageText);
                            }
                        }
                    }
                }
                //Email notification
                //Check if we have more thn one message category
                $multiMessage = 0;
                //Check for update overdue first
                $useUpdateOverdue = false;
                $updateOverdueNotification = json_decode($this->ReadPropertyString('DailyNotificationDeviceStatusUpdateOverdue'), true);
                if ($updateOverdueNotification[0]['Use'] && $updateOverdueNotification[0]['UseMailer']) {
                    $useUpdateOverdue = true;
                    $multiMessage++;
                }
                //Check for low battery is next
                $useLowBattery = false;
                $lowBatteryNotification = json_decode($this->ReadPropertyString('DailyNotificationDeviceStatusLowBattery'), true);
                if ($lowBatteryNotification[0]['Use'] && $lowBatteryNotification[0]['UseMailer']) {
                    $useLowBattery = true;
                    $multiMessage++;
                }
                //Check for normal status is last
                $useNormalStatus = false;
                $normalStatusNotification = json_decode($this->ReadPropertyString('DailyNotificationDeviceStatusOK'), true);
                if ($normalStatusNotification[0]['Use'] && $normalStatusNotification[0]['UseMailer']) {
                    $useNormalStatus = true;
                    $multiMessage++;
                }
                $sendMail = false;
                $messageText = '';
                //Get the message blocks
                $updateOverdueMessages = $this->GetMessageBlockDeviceStatusUpdateOverdue('DailyNotificationDeviceStatusUpdateOverdue', 'DailyNotificationListDeviceStatusUpdateOverdue');
                $lowBatteryMessages = $this->GetMessageBlockDeviceStatusLowBattery('DailyNotificationDeviceStatusLowBattery', 'DailyNotificationListDeviceStatusLowBattery');
                $normalStatusMessages = $this->GetMessageBlockDeviceStatusNormal('DailyNotificationDeviceStatusOK', 'DailyNotificationListDeviceStatusUpdateOverdue', 'DailyNotificationListDeviceStatusLowBattery');
                //We only have one category
                if ($multiMessage == 1) {
                    if ($useUpdateOverdue) {
                        if (strpos($updateOverdueMessages, 'Keine') === false) {
                            $sendMail = true;
                            $messageText .= $updateOverdueMessages;
                        }
                    }
                    if ($useLowBattery) {
                        if (strpos($lowBatteryMessages, 'Keine') === false) {
                            $sendMail = true;
                            $messageText .= $lowBatteryMessages;
                        }
                    }
                    if ($useNormalStatus) {
                        if (strpos($normalStatusMessages, 'Keine') === false) {
                            $sendMail = true;
                            $messageText .= $normalStatusMessages;
                        }
                    }
                }
                //We have more than one category
                if ($multiMessage > 1) {
                    $sendMail = false;
                    if ($useUpdateOverdue) {
                        if (strpos($updateOverdueMessages, 'Keine') === false) {
                            $sendMail = true;
                            $messageText .= $updateOverdueMessages;
                        }
                    }
                    if ($useLowBattery) {
                        if (strpos($lowBatteryMessages, 'Keine') === false) {
                            $sendMail = true;
                            $messageText .= $lowBatteryMessages;
                        }
                    }
                    if ($useNormalStatus) {
                        if (strpos($normalStatusMessages, 'Keine') === false) {
                            $sendMail = true;
                            $messageText .= $normalStatusMessages;
                        }
                    }
                }
                $this->SendDebug(__FUNCTION__, 'E-Mail Versand: ' . json_encode($sendMail), 0);
                if ($sendMail) {
                    $subject = 'Batteriemelder, Tagesbericht vom ' . $timeStamp;
                    $locationDesignation = $this->ReadPropertyString('LocationDesignation');
                    if ($locationDesignation != '') {
                        $subject = $subject . ', Standort: ' . $locationDesignation;
                    }
                    @BN_SendMailNotification($notificationID, $subject, $messageText);
                }
                //Reset values
                if ($ResetCriticalVariables) {
                    $this->ResetAttribute('DailyNotificationListDeviceStatusLowBattery');
                    $this->ResetAttribute('DailyNotificationListDeviceStatusUpdateOverdue');
                }
            }
        }
    }

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
                //Check the overall status first
                //Normal status
                $overallStatus = 0;
                $overallStatusText = 'OK';
                $overallStatusNotificationName = 'WeeklyNotificationTotalStatusOK';
                //Update overdue
                if (!empty(json_decode($this->ReadAttributeString('WeeklyNotificationListDeviceStatusUpdateOverdue'), true))) {
                    $overallStatus = 1;
                }
                //Low battery
                if (!empty(json_decode($this->ReadAttributeString('WeeklyNotificationListDeviceStatusLowBattery'), true))) {
                    $overallStatus = 1;
                }
                if ($overallStatus == 1) {
                    $overallStatusNotificationName = 'WeeklyNotificationTotalStatusAlarm';
                    $overallStatusText = 'Alarm';
                }
                $this->SendDebug(__FUNCTION__, 'Gesamtstatus: ' . $overallStatusText, 0);
                $notificationID = $this->ReadPropertyInteger('WeeklyNotification');
                if ($notificationID > 1 && @IPS_ObjectExists($notificationID)) { //0 = main category, 1 = none
                    $overallStatusNotification = json_decode($this->ReadPropertyString($overallStatusNotificationName), true);
                    if ($overallStatusNotification[0]['Use']) {
                        $messageText = $overallStatusNotification[0]['MessageText'];
                        if ($overallStatusNotification[0]['UseTimestamp']) {
                            $messageText = $messageText . ' ' . $timeStamp;
                        }
                        $this->SendDebug(__FUNCTION__, 'Meldungstext: ' . $messageText, 0);
                        //WebFront notification
                        if ($overallStatusNotification[0]['UseWebFrontNotification']) {
                            @BN_SendWebFrontNotification($notificationID, $overallStatusNotification[0]['WebFrontNotificationTitle'], "\n" . $messageText, $overallStatusNotification[0]['WebFrontNotificationIcon'], $overallStatusNotification[0]['WebFrontNotificationDisplayDuration']);
                        }
                        //WebFront push notification
                        if ($overallStatusNotification[0]['UseWebFrontPushNotification']) {
                            @BN_SendWebFrontPushNotification($notificationID, $overallStatusNotification[0]['WebFrontPushNotificationTitle'], "\n" . $messageText, $overallStatusNotification[0]['WebFrontPushNotificationSound'], $overallStatusNotification[0]['WebFrontPushNotificationTargetID']);
                        }
                        //E-Mail
                        if ($overallStatusNotification[0]['UseMailer']) {
                            @BN_SendMailNotification($notificationID, $overallStatusNotification[0]['Subject'], "\n\n" . $messageText);
                        }
                        //SMS
                        if ($overallStatusNotification[0]['UseSMS']) {
                            @BN_SendNexxtMobileSMS($notificationID, $overallStatusNotification[0]['SMSTitle'], "\n\n" . $messageText);
                            @BN_SendSipgateSMS($notificationID, $overallStatusNotification[0]['SMSTitle'], "\n\n" . $messageText);
                        }
                        //Telegram
                        if ($overallStatusNotification[0]['UseTelegram']) {
                            @BN_SendTelegramMessage($notificationID, $overallStatusNotification[0]['TelegramTitle'], "\n\n" . $messageText);
                        }
                    }
                }

                //Check the device status is next
                //Notification for WebFront, WebFront push notification, SMS and Telegram
                //Update overdue
                $updateOverdueNotification = json_decode($this->ReadPropertyString('WeeklyNotificationDeviceStatusUpdateOverdue'), true);
                $updateOverdueVariables = json_decode($this->ReadAttributeString('WeeklyNotificationListDeviceStatusUpdateOverdue'), true);
                foreach ($updateOverdueVariables as $updateOverdueVariable) {
                    if ($updateOverdueNotification[0]['Use']) {
                        $messageText = $updateOverdueNotification[0]['MessageText'];
                        //Check for placeholder
                        if (strpos($messageText, '%1$s') !== false) {
                            $messageText = sprintf($messageText, $updateOverdueVariable['Name']);
                        }
                        if ($updateOverdueNotification[0]['UseTimestamp']) {
                            $messageText = $messageText . ' ' . $updateOverdueVariable['Timestamp'];
                        }
                        //WebFront notification
                        if ($updateOverdueNotification[0]['UseWebFrontNotification']) {
                            @BN_SendWebFrontNotification($notificationID, $updateOverdueNotification[0]['WebFrontNotificationTitle'], "\n" . $messageText, $updateOverdueNotification[0]['WebFrontNotificationIcon'], $updateOverdueNotification[0]['WebFrontNotificationDisplayDuration']);
                        }
                        //WebFront push notification
                        if ($updateOverdueNotification[0]['UseWebFrontPushNotification']) {
                            @BN_SendWebFrontPushNotification($notificationID, $updateOverdueNotification[0]['WebFrontPushNotificationTitle'], "\n" . $messageText, $updateOverdueNotification[0]['WebFrontPushNotificationSound'], $updateOverdueNotification[0]['WebFrontPushNotificationTargetID']);
                        }
                        //SMS
                        if ($updateOverdueNotification[0]['UseSMS']) {
                            @BN_SendNexxtMobileSMS($notificationID, $updateOverdueNotification[0]['SMSTitle'], "\n\n" . $messageText);
                            @BN_SendSipgateSMS($notificationID, $updateOverdueNotification[0]['SMSTitle'], "\n\n" . $messageText);
                        }
                        //Telegram
                        if ($updateOverdueNotification[0]['UseTelegram']) {
                            @BN_SendTelegramMessage($notificationID, $updateOverdueNotification[0]['TelegramTitle'], "\n\n" . $messageText);
                        }
                    }
                }
                //Low battery
                $lowBatteryNotification = json_decode($this->ReadPropertyString('WeeklyNotificationDeviceStatusLowBattery'), true);
                $lowBatteryVariables = json_decode($this->ReadAttributeString('WeeklyNotificationListDeviceStatusLowBattery'), true);
                foreach ($lowBatteryVariables as $lowBatteryVariable) {
                    if ($lowBatteryNotification[0]['Use']) {
                        $messageText = $lowBatteryNotification[0]['MessageText'];
                        //Check for placeholder
                        if (strpos($messageText, '%1$s') !== false) {
                            $messageText = sprintf($messageText, $lowBatteryVariable['Name']);
                        }
                        if ($lowBatteryNotification[0]['UseTimestamp']) {
                            $messageText = $messageText . ' ' . $lowBatteryVariable['Timestamp'];
                        }
                        //WebFront notification
                        if ($lowBatteryNotification[0]['UseWebFrontNotification']) {
                            @BN_SendWebFrontNotification($notificationID, $lowBatteryNotification[0]['WebFrontNotificationTitle'], "\n" . $messageText, $lowBatteryNotification[0]['WebFrontNotificationIcon'], $lowBatteryNotification[0]['WebFrontNotificationDisplayDuration']);
                        }
                        //WebFront push notification
                        if ($lowBatteryNotification[0]['UseWebFrontPushNotification']) {
                            @BN_SendWebFrontPushNotification($notificationID, $lowBatteryNotification[0]['WebFrontPushNotificationTitle'], "\n" . $messageText, $lowBatteryNotification[0]['WebFrontPushNotificationSound'], $lowBatteryNotification[0]['WebFrontPushNotificationTargetID']);
                        }
                        //SMS
                        if ($lowBatteryNotification[0]['UseSMS']) {
                            @BN_SendNexxtMobileSMS($notificationID, $lowBatteryNotification[0]['SMSTitle'], "\n\n" . $messageText);
                            @BN_SendSipgateSMS($notificationID, $lowBatteryNotification[0]['SMSTitle'], "\n\n" . $messageText);
                        }
                        //Telegram
                        if ($lowBatteryNotification[0]['UseTelegram']) {
                            @BN_SendTelegramMessage($notificationID, $lowBatteryNotification[0]['TelegramTitle'], "\n\n" . $messageText);
                        }
                    }
                }
                //Normal status
                $normalStatusNotification = json_decode($this->ReadPropertyString('WeeklyNotificationDeviceStatusOK'), true);
                $normalStatusVariables = json_decode($this->GetMonitoredVariables(), true);
                foreach ($normalStatusVariables as $normalStatusVariable) {
                    $variableID = $normalStatusVariable['ID'];
                    if ($variableID > 1 && @IPS_ObjectExists($variableID)) {
                        if (in_array($variableID, array_column(json_decode($this->ReadAttributeString('WeeklyNotificationListDeviceStatusUpdateOverdue'), true), 'ID'))) {
                            continue;
                        }
                        if (in_array($variableID, array_column(json_decode($this->ReadAttributeString('WeeklyNotificationListDeviceStatusLowBattery'), true), 'ID'))) {
                            continue;
                        }
                        if ($normalStatusNotification[0]['Use']) {
                            $messageText = $normalStatusNotification[0]['MessageText'];
                            //Check for placeholder
                            if (strpos($messageText, '%1$s') !== false) {
                                $messageText = sprintf($messageText, $normalStatusVariable['Name']);
                            }
                            if ($normalStatusNotification[0]['UseTimestamp']) {
                                $messageText = $messageText . ' ' . $timeStamp;
                            }
                            //WebFront notification
                            if ($normalStatusNotification[0]['UseWebFrontNotification']) {
                                @BN_SendWebFrontNotification($notificationID, $normalStatusNotification[0]['WebFrontNotificationTitle'], "\n" . $messageText, $normalStatusNotification[0]['WebFrontNotificationIcon'], $normalStatusNotification[0]['WebFrontNotificationDisplayDuration']);
                            }
                            //WebFront push notification
                            if ($normalStatusNotification[0]['UseWebFrontPushNotification']) {
                                @BN_SendWebFrontPushNotification($notificationID, $normalStatusNotification[0]['WebFrontPushNotificationTitle'], "\n" . $messageText, $normalStatusNotification[0]['WebFrontPushNotificationSound'], $normalStatusNotification[0]['WebFrontPushNotificationTargetID']);
                            }
                            //SMS
                            if ($normalStatusNotification[0]['UseSMS']) {
                                @BN_SendNexxtMobileSMS($notificationID, $normalStatusNotification[0]['SMSTitle'], "\n\n" . $messageText);
                                @BN_SendSipgateSMS($notificationID, $normalStatusNotification[0]['SMSTitle'], "\n\n" . $messageText);
                            }
                            //Telegram
                            if ($normalStatusNotification[0]['UseTelegram']) {
                                @BN_SendTelegramMessage($notificationID, $normalStatusNotification[0]['TelegramTitle'], "\n\n" . $messageText);
                            }
                        }
                    }
                }
                //Email notification
                //Check if we have more thn one message category
                $multiMessage = 0;
                //Check for update overdue first
                $useUpdateOverdue = false;
                $updateOverdueNotification = json_decode($this->ReadPropertyString('WeeklyNotificationDeviceStatusUpdateOverdue'), true);
                if ($updateOverdueNotification[0]['Use'] && $updateOverdueNotification[0]['UseMailer']) {
                    $useUpdateOverdue = true;
                    $multiMessage++;
                }
                //Check for low battery is next
                $useLowBattery = false;
                $lowBatteryNotification = json_decode($this->ReadPropertyString('WeeklyNotificationDeviceStatusLowBattery'), true);
                if ($lowBatteryNotification[0]['Use'] && $lowBatteryNotification[0]['UseMailer']) {
                    $useLowBattery = true;
                    $multiMessage++;
                }
                //Check for normal status is last
                $useNormalStatus = false;
                $normalStatusNotification = json_decode($this->ReadPropertyString('WeeklyNotificationDeviceStatusOK'), true);
                if ($normalStatusNotification[0]['Use'] && $normalStatusNotification[0]['UseMailer']) {
                    $useNormalStatus = true;
                    $multiMessage++;
                }
                $sendMail = false;
                $messageText = '';
                //Get the message blocks
                $updateOverdueMessages = $this->GetMessageBlockDeviceStatusUpdateOverdue('WeeklyNotificationDeviceStatusUpdateOverdue', 'WeeklyNotificationListDeviceStatusUpdateOverdue');
                $lowBatteryMessages = $this->GetMessageBlockDeviceStatusLowBattery('WeeklyNotificationDeviceStatusLowBattery', 'WeeklyNotificationListDeviceStatusLowBattery');
                $normalStatusMessages = $this->GetMessageBlockDeviceStatusNormal('WeeklyNotificationDeviceStatusOK', 'WeeklyNotificationListDeviceStatusUpdateOverdue', 'WeeklyNotificationListDeviceStatusLowBattery');
                //We only have one category
                if ($multiMessage == 1) {
                    if ($useUpdateOverdue) {
                        if (strpos($updateOverdueMessages, 'Keine') === false) {
                            $sendMail = true;
                            $messageText .= $updateOverdueMessages;
                        }
                    }
                    if ($useLowBattery) {
                        if (strpos($lowBatteryMessages, 'Keine') === false) {
                            $sendMail = true;
                            $messageText .= $lowBatteryMessages;
                        }
                    }
                    if ($useNormalStatus) {
                        if (strpos($normalStatusMessages, 'Keine') === false) {
                            $sendMail = true;
                            $messageText .= $normalStatusMessages;
                        }
                    }
                }
                //We have more than one category
                if ($multiMessage > 1) {
                    $sendMail = true;
                    if ($useUpdateOverdue) {
                        $messageText .= $updateOverdueMessages;
                    }
                    if ($useLowBattery) {
                        $messageText .= $lowBatteryMessages;
                    }
                    if ($useNormalStatus) {
                        $messageText .= $normalStatusMessages;
                    }
                }
                if ($sendMail) {
                    $this->SendDebug(__FUNCTION__, 'E-Mail wird versendet: ', 0);
                    $subject = 'Batteriemelder, Wochenbericht vom ' . $timeStamp;
                    $locationDesignation = $this->ReadPropertyString('LocationDesignation');
                    if ($locationDesignation != '') {
                        $subject = $subject . ', Standort: ' . $locationDesignation;
                    }
                    $this->SendDebug(__FUNCTION__, 'Nachrichtentext: ' . $messageText, 0);
                    @BN_SendMailNotification($notificationID, $subject, $messageText);
                }
                //Reset values
                if ($ResetCriticalVariables) {
                    $this->ResetAttribute('WeeklyNotificationListDeviceStatusLowBattery');
                    $this->ResetAttribute('WeeklyNotificationListDeviceStatusUpdateOverdue');
                }
            }
        }
    }

    #################### Private

    /**
     * Gets the message block for devices with an update overdue.
     *
     * @param string $NotificationName
     * Name of the notification type.
     *
     * @param string $UpdateOverdueListName
     * Name of the attribute which contains the critical variables.
     *
     * @return string
     * Returns the message block.
     *
     * @throws Exception
     */
    private function GetMessageBlockDeviceStatusUpdateOverdue(string $NotificationName, string $UpdateOverdueListName): string
    {
        $updateOverdueNotification = json_decode($this->ReadPropertyString($NotificationName), true);
        $messageText = $updateOverdueNotification[0]['Subject'] . ":\n\n";
        $criticalVariables = json_decode($this->ReadAttributeString($UpdateOverdueListName), true);
        $existing = false;
        foreach ($criticalVariables as $variable) {
            $existing = true;
            $lineText = $updateOverdueNotification[0]['MessageText'];
            //Check for placeholder
            if (strpos($lineText, '%1$s') !== false) {
                $name = $variable['Name'];
                if ($variable['Comment'] != '') {
                    $name = $name . ', ' . $variable['Comment'];
                }
                $lineText = sprintf($lineText, $name);
            }
            if ($updateOverdueNotification[0]['UseTimestamp']) {
                $lineText = $lineText . ' ' . $variable['Timestamp'];
            }
            $messageText .= $lineText . "\n";
        }
        if (!$existing) {
            $messageText .= 'Keine';
        }
        $messageText .= "\n\n\n\n";
        return $messageText;
    }

    /**
     * Gets the message block for devices with low battery.
     *
     * @param string $NotificationName
     * Name of the notification type.
     *
     * @param string $LowBatteryListName
     * Name of the attribute which contains the critical variables.
     *
     * @return string
     * Returns the message block.
     *
     * @throws Exception
     */
    private function GetMessageBlockDeviceStatusLowBattery(string $NotificationName, string $LowBatteryListName): string
    {
        $lowBatteryNotification = json_decode($this->ReadPropertyString($NotificationName), true);
        $messageText = $lowBatteryNotification[0]['Subject'] . ":\n\n";
        $criticalVariables = json_decode($this->ReadAttributeString($LowBatteryListName), true);
        $existing = false;
        foreach ($criticalVariables as $variable) {
            $existing = true;
            $lineText = $lowBatteryNotification[0]['MessageText'];
            //Check for placeholder
            if (strpos($lineText, '%1$s') !== false) {
                $name = $variable['Name'];
                if ($variable['Comment'] != '') {
                    $name = $name . ', ' . $variable['Comment'];
                }
                $lineText = sprintf($lineText, $name);
            }
            if ($lowBatteryNotification[0]['UseTimestamp']) {
                $lineText = $lineText . ' ' . $variable['Timestamp'];
            }
            $messageText .= $lineText . "\n";
        }
        if (!$existing) {
            $messageText .= 'Keine';
        }
        $messageText .= "\n\n\n\n";
        return $messageText;
    }

    /**
     * Gets the message block for device with normal status.
     *
     * @param string $NotificationName
     * Name of the notification type
     *
     * @param string $UpdateOverdueListName
     * Name of the attribute list which contains the variables with an update overdue.
     *
     * @param string $LowBatteryListName
     * Name of the attribute list which contains the variables with low battery.
     *
     * @return string
     * Returns the message block.
     *
     * @throws Exception
     */
    private function GetMessageBlockDeviceStatusNormal(string $NotificationName, string $UpdateOverdueListName, string $LowBatteryListName): string
    {
        $normalStatusNotification = json_decode($this->ReadPropertyString($NotificationName), true);
        $messageText = $normalStatusNotification[0]['Subject'] . ":\n\n";
        $monitoredVariables = json_decode($this->GetMonitoredVariables(), true);
        $existing = false;
        foreach ($monitoredVariables as $variable) {
            $id = $variable['ID'];
            if ($id > 1 && @IPS_ObjectExists($id)) {
                if (in_array($id, array_column(json_decode($this->ReadAttributeString($UpdateOverdueListName), true), 'ID'))) {
                    continue;
                }
                if (in_array($id, array_column(json_decode($this->ReadAttributeString($LowBatteryListName), true), 'ID'))) {
                    continue;
                }
                $existing = true;
                $lineText = $normalStatusNotification[0]['MessageText'];
                //Check for placeholder
                if (strpos($lineText, '%1$s') !== false) {
                    $name = $variable['Name'];
                    if ($variable['Comment'] != '') {
                        $name = $name . ', ' . $variable['Comment'];
                    }
                    $lineText = sprintf($lineText, $name);
                }
                if ($normalStatusNotification[0]['UseTimestamp']) {
                    $lineText = $lineText . ' ' . date('d.m.Y, H:i:s');
                }
                $messageText .= $lineText . "\n";
            }
        }
        if (!$existing) {
            $messageText .= 'Keine';
        }
        $messageText .= "\n\n\n\n";
        return $messageText;
    }
}