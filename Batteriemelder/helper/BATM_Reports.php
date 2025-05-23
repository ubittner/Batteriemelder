<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait BATM_Reports
{
    ########## Public

    public function ExecuteDailyNotification(bool $CheckDay, bool $ResetCriticalVariables): void
    {
        $this->SetTimerInterval('DailyNotification', $this->GetInterval('DailyNotificationTime'));
        if ($this->GetValue('Active')) {
            $execute = true;
            if ($CheckDay) {
                $execute = $this->CheckDayForDailyNotification();
            }
            if ($execute) {
                $this->SendDebug(__FUNCTION__, 'Tagesbericht wird versendet...', 0);
                $this->SendReportAsNotification('Daily', 'Notification');
                $this->SendReportAsNotification('Daily', 'PushNotification');
                $this->SendReportAsNotification('Daily', 'PostNotification');
                $this->SendReportAsMail('Daily');
            }
        }
        //Reset critical variables
        if ($this->ReadPropertyBoolean('DailyNotificationAlwaysResetCriticalVariables') || $ResetCriticalVariables) {
            $this->ResetAttribute('DailyNotificationListDeviceStatusEmptyBattery');
            $this->ResetAttribute('DailyNotificationListDeviceStatusLowBattery');
            $this->ResetAttribute('DailyNotificationListDeviceStatusBatteryOK');
        }
    }

    public function ExecuteWeeklyNotification(bool $CheckDay, bool $ResetCriticalVariables): void
    {
        $this->SetTimerInterval('WeeklyNotification', $this->GetInterval('WeeklyNotificationTime'));
        if ($this->GetValue('Active')) {
            $execute = true;
            if ($CheckDay) {
                $execute = $this->CheckDayForWeeklyNotification();
            }
            if ($execute) {
                $this->SendDebug(__FUNCTION__, 'Wochenbericht wird versendet...', 0);
                $this->SendReportAsNotification('Weekly', 'Notification');
                $this->SendReportAsNotification('Weekly', 'PushNotification');
                $this->SendReportAsNotification('Weekly', 'PostNotification');
                $this->SendReportAsMail('Weekly');
            }
        }
        //Reset critical variables
        if ($ResetCriticalVariables) {
            $this->ResetAttribute('WeeklyNotificationListDeviceStatusEmptyBattery');
            $this->ResetAttribute('WeeklyNotificationListDeviceStatusLowBattery');
            $this->ResetAttribute('WeeklyNotificationListDeviceStatusBatteryOK');
        }
    }

    public function CheckDayForDailyNotification(): bool
    {
        $weekday = date('l');
        $this->SendDebug(__FUNCTION__, 'Check day for daily notification: ' . $weekday, 0);
        $useDay = $this->ReadPropertyBoolean('DailyNotification' . $weekday);
        $this->SendDebug(__FUNCTION__, 'Selected day is ' . json_encode($useDay), 0);
        if ($useDay) {
            $this->SendDebug(__FUNCTION__, 'Day is ' . $weekday . ', so execute daily notification', 0);
            return true;
        }
        $this->SendDebug(__FUNCTION__, 'Day is ' . $weekday . ', so do not execute daily notification', 0);
        return false;
    }

    public function CheckDayForWeeklyNotification(): bool
    {
        //Check weekday
        $weekday = date('w');
        $this->SendDebug(__FUNCTION__, 'Check day for weekly notification: ' . $weekday, 0);
        $useDay = $this->ReadPropertyInteger('WeeklyNotificationDay');
        if ($weekday == $useDay) {
            $this->SendDebug(__FUNCTION__, 'Day is ' . $weekday . ', so execute weekly notification', 0);
            return true;
        }
        $this->SendDebug(__FUNCTION__, 'Day is ' . $weekday . ', so do not execute weekly notification', 0);
        return false;
    }

    public function IsVariableAlreadyOnNotificationList(int $VariableID, string $NotificationList): bool
    {
        $listedVariables = json_decode($this->ReadAttributeString($NotificationList), true);
        return in_array($VariableID, array_column($listedVariables, 'ID'));
    }

    ########## Protected

    protected function GetValueFromMonitoredVariable(int $VariableID, string $ValueName): string
    {
        $result = '';
        $monitoredVariables = json_decode($this->ReadAttributeString('MonitoredVariables'), true);
        $key = array_search($VariableID, array_column($monitoredVariables, 'ID'));
        if (is_int($key)) {
            if (array_key_exists($ValueName, $monitoredVariables[$key])) {
                $result = (string) $monitoredVariables[$key][$ValueName];
                $this->SendDebug(__FUNCTION__, 'Value ' . $ValueName . ' for variable ' . $VariableID . ' is: ' . $result, 0);
            }
        }
        return $result;
    }

    protected function IsNotificationTypeValid(string $NotificationType): bool
    {
        $notificationTypes = [
            'Immediate',
            'Daily',
            'Weekly'
        ];
        if (in_array($NotificationType, $notificationTypes)) {
            return true;
        }
        return false;
    }

    protected function IsNotificationMethodValid(string $NotificationMethod): bool
    {
        $notificationMethods = [
            'Notification',
            'PushNotification',
            'PostNotification',
            'MailerNotification'
        ];
        if (in_array($NotificationMethod, $notificationMethods)) {
            return true;
        }
        return false;
    }

    ########## Private

    private function SendReportAsNotification(string $NotificationType, string $NotificationMethod): void
    {
        if (!$this->GetValue('Active')) {
            return;
        }
        $isTypeValid = $this->IsNotificationTypeValid($NotificationType);
        if (!$isTypeValid || $NotificationType == 'Immediate') {
            return;
        }
        $isMethodValid = $this->IsNotificationMethodValid($NotificationMethod);
        if (!$isMethodValid || $NotificationMethod == 'MailerNotification') {
            return;
        }
        foreach (json_decode($this->ReadPropertyString($NotificationType . $NotificationMethod), true) as $notification) {
            if (!$notification['Use'] || $notification['ID'] <= 1 || @!IPS_ObjectExists($notification['ID'])) {
                $this->SendDebug(__FUNCTION__, 'Notification ' . $notification['ID'] . ' does not exist or is not active', 0);
                continue;
            }
            foreach (['BatteryOK', 'LowBattery', 'EmptyBattery'] as $batteryState) {
                if (!$notification['Use' . $batteryState]) {
                    continue;
                }
                //Get variables from the notification list
                $variables = json_decode($this->ReadAttributeString($NotificationType . 'NotificationListDeviceStatus' . $batteryState), true);
                foreach ($variables as $variable) {
                    if ($NotificationMethod == 'Notification') {
                        //Title
                        $title = $notification[$batteryState . 'Title'];
                        //Text
                        $text = $notification[$batteryState . 'MessageText'];
                    } else {
                        //Title length max 32 characters
                        $title = substr($notification[$batteryState . 'Title'], 0, 32);
                        //Text
                        $text = "\n" . $notification[$batteryState . 'MessageText'];
                    }
                    //Check for placeholder
                    if (strpos($text, '%1$s') !== false) {
                        $text = sprintf($text, $this->GetValueFromMonitoredVariable($variable['ID'], 'Name'));
                    }
                    //Battery type
                    if ($notification['Use' . $batteryState . 'BatteryType']) {
                        $batteryType = $this->GetValueFromMonitoredVariable($variable['ID'], 'BatteryType');
                        if ($batteryType != '') {
                            $text .= ', ' . $this->GetValueFromMonitoredVariable($variable['ID'], 'BatteryType');
                        }
                    }
                    //Timestamp
                    if ($notification['Use' . $batteryState . 'Timestamp']) {
                        $text .= ', ' . $variable['Timestamp'];
                    }
                    IPS_Sleep(100);
                    if (!$NotificationMethod == 'Notification') {
                        //Text length max 256 characters
                        $text = substr($text, 0, 256);
                    }
                    if ($NotificationMethod == 'Notification') {
                        $this->SendNotification($notification['ID'], $notification[$batteryState . 'Title'], $text, $notification[$batteryState . 'Icon'], $notification[$batteryState . 'DisplayDuration']);
                    }
                    if ($NotificationMethod == 'PushNotification') {
                        $this->SendPushNotification($notification['ID'], $title, $text, $notification[$batteryState . 'Sound'], $notification[$batteryState . 'TargetID']);
                    }
                    if ($NotificationMethod == 'PostNotification') {
                        $this->SendPostNotification($notification['ID'], $title, $text, $notification[$batteryState . 'Icon'], $notification[$batteryState . 'Sound'], $notification[$batteryState . 'TargetID']);
                    }
                }
            }
        }
    }

    private function SendReportAsMail(string $NotificationType): void
    {
        if (!$this->GetValue('Active')) {
            return;
        }
        $typeIsValid = $this->IsNotificationTypeValid($NotificationType);
        if (!$typeIsValid || $NotificationType == 'Immediate') {
            return;
        }
        foreach (json_decode($this->ReadPropertyString($NotificationType . 'MailerNotification'), true) as $mailer) {
            if (!$mailer['Use'] || $mailer['ID'] <= 1 || @!IPS_ObjectExists($mailer['ID'])) {
                $this->SendDebug(__FUNCTION__, 'Mailer ' . $mailer['ID'] . ' does not exist or is not active', 0);
                continue;
            }
            $messageText = 'Tagesbericht vom ' . date('d.m.Y, H:i:s') . ":\n\n\n";
            if ($NotificationType == 'Weekly') {
                $messageText = 'Wochenbericht vom ' . date('d.m.Y, H:i:s') . ":\n\n\n";
            }
            $batteryStates = ['EmptyBattery', 'LowBattery', 'BatteryOK'];
            foreach ($batteryStates as $batteryState) {
                if (!$mailer['Use' . $batteryState]) {
                    continue;
                }
                //Header
                if ($batteryState == 'EmptyBattery') {
                    $messageText .= "Batterie leer:\n\n";
                }
                if ($batteryState == 'LowBattery') {
                    $messageText .= "Batterie schwach:\n\n";
                }
                if ($batteryState == 'BatteryOK') {
                    $messageText .= "Batterie OK:\n\n";
                }
                //Get variables from the notification list
                $variables = json_decode($this->ReadAttributeString($NotificationType . 'NotificationListDeviceStatus' . $batteryState), true);
                if (empty($variables)) {
                    $messageText .= "Keine\n\n";
                } else {
                    foreach ($variables as $variable) {
                        //Text
                        $lineText = $mailer[$batteryState . 'MessageText'];
                        $name = $this->GetValueFromMonitoredVariable($variable['ID'], 'Name');
                        if ($this->GetValueFromMonitoredVariable($variable['ID'], 'Comment') != '') {
                            $name = $name . ', ' . $this->GetValueFromMonitoredVariable($variable['ID'], 'Comment');
                        }
                        //Check for placeholder
                        if (strpos($lineText, '%1$s') !== false) {
                            $lineText = sprintf($lineText, $name);
                        }
                        //Timestamp
                        if ($mailer['Use' . $batteryState . 'Timestamp']) {
                            $lineText .= ', ' . $variable['Timestamp'];
                        }
                        //Variable ID
                        if ($mailer['Use' . $batteryState . 'VariableID']) {
                            $lineText .= ', ID: ' . $variable['ID'];
                        }
                        //Battery type
                        if ($mailer['Use' . $batteryState . 'BatteryType']) {
                            $batteryType = $this->GetValueFromMonitoredVariable($variable['ID'], 'BatteryType');
                            if ($batteryType != '') {
                                $lineText .= ', Batterietyp: ' . $batteryType;
                            }
                        }
                        $messageText .= $lineText . "\n";
                    }
                    $messageText .= "\n";
                }
            }
            $this->SendMail($mailer['ID'], $mailer['Subject'], $messageText);
            IPS_Sleep(100);
        }
    }
}