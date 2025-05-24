<?php

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection DuplicatedCode */

declare(strict_types=1);

trait BATM_Notifications
{
    ########## Public

    public function ShowNotificationListContent(string $NotificationType): void
    {
        if (!$this->IsNotificationTypeValid($NotificationType)) {
            return;
        }
        $rowColor['EmptyBattery'] = '#FFC0C0'; # red
        $rowColor['LowBattery'] = '#FFFFC0'; # yellow
        $rowColor['BatteryOK'] = '#C0FFC0'; # green
        $batteryStates = ['EmptyBattery', 'LowBattery', 'BatteryOK'];
        foreach ($batteryStates as $batteryState) {
            $this->UpdateFormField($NotificationType . 'Notification' . $batteryState . 'ConfigurationButton', 'visible', false);
            $result = [];
            //Get the variables from the notification list
            $variables = json_decode($this->ReadAttributeString($NotificationType . 'NotificationListDeviceStatus' . $batteryState), true);
            $amount = count($variables);
            if ($amount == 0) {
                $amount = 1;
            }
            $this->UpdateFormField($NotificationType . 'NotificationListDeviceStatus' . $batteryState, 'rowCount', $amount);
            foreach ($variables as $variable) {
                $id = $variable['ID'];
                if (@!IPS_ObjectExists($id)) {
                    continue;
                }
                $result[] = [
                    'ID'          => $id,
                    'Name'        => $this->GetValueFromMonitoredVariable($id, 'Name'),
                    'Comment'     => $this->GetValueFromMonitoredVariable($id, 'Comment'),
                    'BatteryType' => $this->GetValueFromMonitoredVariable($id, 'BatteryType'),
                    'Timestamp'   => $variable['Timestamp'],
                    'rowColor'    => $rowColor[$batteryState]
                ];
            }
            $this->UpdateFormField($NotificationType . 'NotificationListDeviceStatus' . $batteryState, 'values', json_encode($result));
        }
    }

    public function DeleteVariableFromNotificationList(string $NotificationList, int $VariableID): void
    {
        $elements = json_decode($this->ReadAttributeString($NotificationList), true);
        foreach ($elements as $key => $element) {
            if ($element['ID'] == $VariableID) {
                unset($elements[$key]);
            }
        }
        $elements = array_values($elements);
        $this->WriteAttributeString($NotificationList, json_encode($elements));
    }

    public function ResetNotificationLists(): void
    {
        $notificationLists = [
            'ImmediateNotificationListDeviceStatusEmptyBattery',
            'ImmediateNotificationListDeviceStatusLowBattery',
            'ImmediateNotificationListDeviceStatusBatteryOK',
            'DailyNotificationListDeviceStatusEmptyBattery',
            'DailyNotificationListDeviceStatusLowBattery',
            'DailyNotificationListDeviceStatusBatteryOK',
            'WeeklyNotificationListDeviceStatusEmptyBattery',
            'WeeklyNotificationListDeviceStatusLowBattery',
            'WeeklyNotificationListDeviceStatusBatteryOK'
        ];
        foreach ($notificationLists as $notificationList) {
            $this->WriteAttributeString($notificationList, '[]');
        }
    }

    ########## Protected

    protected function UpdateNotificationLists(string $BatteryList): void
    {
        //Check the battery list first
        if ($BatteryList == '' || !$this->IsStringJsonEncoded($BatteryList)) {
            return;
        }
        //Reset the daily and weekly notification lists for normal battery state
        $notificationLists = ['DailyNotificationListDeviceStatusBatteryOK', 'WeeklyNotificationListDeviceStatusBatteryOK'];
        foreach ($notificationLists as $notificationList) {
            $this->WriteAttributeString($notificationList, '[]');
        }
        //Check monitored variables
        $monitoredVariables = json_decode($BatteryList, true);
        foreach ($monitoredVariables as $monitoredVariable) {
            $status = $monitoredVariable['Status'];
            $addToList = false;
            $notificationLists = [];

            # Battery OK

            if ($status == 'BatteryOK') {

                /*
                 * Immediate notification
                 *
                 * Immediate notification is handled differently from the daily and weekly notification.
                 * Critical states such as empty or low battery are immediately added to the notification list.
                 * The status is only added to the notification list when it is no longer critical.
                 * We want to avoid triggering the same battery notification several times a day.
                 *
                 */

                //Check if the battery was empty or low before
                $listed = $this->IsVariableAlreadyOnNotificationList($monitoredVariable['ID'], 'ImmediateNotificationListDeviceStatusEmptyBattery') || $this->IsVariableAlreadyOnNotificationList($monitoredVariable['ID'], 'ImmediateNotificationListDeviceStatusLowBattery');
                if ($listed) {
                    $this->AddVariableToNotificationList($monitoredVariable['ID'], $monitoredVariable['Timestamp'], 'ImmediateNotificationListDeviceStatusBatteryOK');
                    $this->SendImmediateNotification($status, json_encode($monitoredVariable));
                    $this->SendImmediateMailNotification($status, json_encode($monitoredVariable));
                }

                /*
                 * Daily and weekly notification
                 *
                 * We will always build the list from scratch.
                 *
                 */

                $notificationLists = ['DailyNotificationListDeviceStatusBatteryOK', 'WeeklyNotificationListDeviceStatusBatteryOK'];
                foreach ($notificationLists as $notificationList) {
                    $listedVariables = json_decode($this->ReadAttributeString($notificationList), true);
                    $listedVariables[] = [
                        'ID'          => $monitoredVariable['ID'],
                        'Timestamp'   => $monitoredVariable['Timestamp']
                    ];
                    $this->WriteAttributeString($notificationList, json_encode($listedVariables));
                }
            }

            # Low battery

            if ($status == 'LowBattery') {
                $addToList = true;
                $notificationLists = ['ImmediateNotificationListDeviceStatusLowBattery', 'DailyNotificationListDeviceStatusLowBattery', 'WeeklyNotificationListDeviceStatusLowBattery'];
            }

            # Empty battery

            if ($status == 'EmptyBattery') {
                $addToList = true;
                $notificationLists = ['ImmediateNotificationListDeviceStatusEmptyBattery', 'DailyNotificationListDeviceStatusEmptyBattery', 'WeeklyNotificationListDeviceStatusEmptyBattery'];
            }

            if ($addToList) {
                foreach ($notificationLists as $notificationList) {
                    $isListed = $this->IsVariableAlreadyOnNotificationList($monitoredVariable['ID'], $notificationList);
                    $this->AddVariableToNotificationList($monitoredVariable['ID'], $monitoredVariable['Timestamp'], $notificationList);
                    if ($notificationList == 'ImmediateNotificationListDeviceStatusEmptyBattery' || $notificationList == 'ImmediateNotificationListDeviceStatusLowBattery') {
                        if (!$isListed) {
                            $this->SendImmediateNotification($status, json_encode($monitoredVariable));
                            $this->SendImmediateMailNotification($status, json_encode($monitoredVariable));
                        }
                    }
                }
            }
        }
    }

    protected function AddVariableToNotificationList(int $VariableID, string $Timestamp, string $NotificationList): bool
    {
        $result = false;
        $variables = json_decode($this->ReadAttributeString($NotificationList), true);
        if (!in_array($VariableID, array_column($variables, 'ID'))) {
            $result = true;
            $variables[] = [
                'ID'        => $VariableID,
                'Timestamp' => $Timestamp
            ];
            $this->WriteAttributeString($NotificationList, json_encode($variables));
        }
        return $result;
    }

    protected function CleanupNotificationLists(string $BatteryList): void
    {
        //Check the battery list first
        if ($BatteryList == '' || !$this->IsStringJsonEncoded($BatteryList)) {
            return;
        }
        $notificationLists = [
            'ImmediateNotificationListDeviceStatusEmptyBattery',
            'ImmediateNotificationListDeviceStatusLowBattery',
            'ImmediateNotificationListDeviceStatusBatteryOK',
            'DailyNotificationListDeviceStatusEmptyBattery',
            'DailyNotificationListDeviceStatusLowBattery',
            'DailyNotificationListDeviceStatusBatteryOK',
            'WeeklyNotificationListDeviceStatusEmptyBattery',
            'WeeklyNotificationListDeviceStatusLowBattery',
            'WeeklyNotificationListDeviceStatusBatteryOK',
        ];
        //Checks whether critical variables are still being monitored. Otherwise, they will be deleted from the notification lists
        foreach ($notificationLists as $notificationList) {
            $changes = false;
            $listedVariables = json_decode($this->ReadAttributeString($notificationList), true);
            foreach ($listedVariables as $key => $listedVariable) {
                //Remove from the notification list
                if (!in_array($listedVariable['ID'], array_column(json_decode($BatteryList, true), 'ID'))) {
                    unset($listedVariables[$key]);
                    $changes = true;
                }
            }
            if ($changes) {
                $this->WriteAttributeString($notificationList, json_encode(array_values($listedVariables)));
            }
        }
    }

    protected function SendNotification(int $InstanceID, string $Title, string $Text, string $Icon, int $Duration): void
    {
        $scriptText = sprintf(
            'WFC_SendNotification(%d, "%s", "%s", "%s", %d);',
            $InstanceID,
            $Title,
            $Text,
            $Icon,
            $Duration
        );
        @IPS_RunScriptText($scriptText);
    }

    protected function SendPushNotification(int $InstanceID, string $Title, string $Text, string $Sound, int $TargetID): void
    {
        $scriptText = sprintf(
            'WFC_PushNotification(%d, "%s", "%s", "%s", %d);',
            $InstanceID,
            $Title,
            $Text,
            $Sound,
            $TargetID
        );
        @IPS_RunScriptText($scriptText);
    }

    protected function SendPostNotification(int $InstanceID, string $Title, string $Text, string $Icon, string $Sound, int $TargetID): void
    {
        $scriptText = sprintf(
            'VISU_PostNotificationEx(%d, "%s", "%s", "%s", "%s", %d);',
            $InstanceID,
            $Title,
            $Text,
            $Icon,
            $Sound,
            $TargetID
        );
        @IPS_RunScriptText($scriptText);
    }

    protected function SendMail(int $InstanceID, string $Subject, string $Text): void
    {
        $scriptText = sprintf(
            'MA_SendMessage(%d, "%s", "%s");',
            $InstanceID,
            $Subject,
            $Text
        );
        @IPS_RunScriptText($scriptText);
    }

    ########## Private

    private function SendImmediateNotification(string $BatteryState, string $Variable): void
    {
        if (!$this->GetValue('Active')) {
            return;
        }
        $batteryStates = ['EmptyBattery', 'LowBattery', 'BatteryOK'];
        if (!in_array($BatteryState, $batteryStates) || $Variable == '' || !$this->IsStringJsonEncoded($Variable)) {
            return;
        }
        $Variable = json_decode($Variable, true);
        $notificationMethods = ['ImmediateNotification', 'ImmediatePushNotification', 'ImmediatePostNotification'];
        foreach ($notificationMethods as $notificationMethod) {
            foreach (json_decode($this->ReadPropertyString($notificationMethod), true) as $notification) {
                if (!$notification['Use']) {
                    continue;
                }
                if ($notification['ID'] <= 1 || @!IPS_ObjectExists($notification['ID'])) {
                    continue;
                }
                if (!$notification['Use' . $BatteryState]) {
                    continue;
                }
                if ($notificationMethod == 'ImmediateNotification') {
                    //Title
                    $title = $notification[$BatteryState . 'Title'];
                    //Text
                    $text = $notification[$BatteryState . 'MessageText'];
                } else {
                    //Title length max 32 characters
                    $title = substr($notification[$BatteryState . 'Title'], 0, 32);
                    //Text
                    $text = "\n" . $notification[$BatteryState . 'MessageText'];
                }
                //Check for placeholder
                if (strpos($text, '%1$s') !== false) {
                    $text = sprintf($text, $this->GetValueFromMonitoredVariable($Variable['ID'], 'Name'));
                }
                //Battery type
                if ($notification['Use' . $BatteryState . 'BatteryType']) {
                    $batteryType = $this->GetValueFromMonitoredVariable($Variable['ID'], 'BatteryType');
                    if ($batteryType != '') {
                        $text .= ', ' . $this->GetValueFromMonitoredVariable($Variable['ID'], 'BatteryType');
                    }
                }
                //Timestamp
                if ($notification['Use' . $BatteryState . 'Timestamp']) {
                    $text .= ', ' . $Variable['Timestamp'];
                }
                if (!$notificationMethod == 'ImmediateNotification') {
                    //Text length max 256 characters
                    $text = substr($text, 0, 256);
                }
                IPS_Sleep(100);
                if ($notificationMethod == 'ImmediateNotification') {
                    $this->SendNotification($notification['ID'], $notification[$BatteryState . 'Title'], $text, $notification[$BatteryState . 'Icon'], $notification[$BatteryState . 'DisplayDuration']);
                }
                if ($notificationMethod == 'ImmediatePushNotification') {
                    $this->SendPushNotification($notification['ID'], $title, $text, $notification[$BatteryState . 'Sound'], $notification[$BatteryState . 'TargetID']);
                }
                if ($notificationMethod == 'ImmediatePostNotification') {
                    $this->SendPostNotification($notification['ID'], $title, $text, $notification[$BatteryState . 'Icon'], $notification[$BatteryState . 'Sound'], $notification[$BatteryState . 'TargetID']);
                }
            }
        }
    }

    private function SendImmediateMailNotification(string $BatteryState, string $Variable): void
    {
        if (!$this->GetValue('Active')) {
            return;
        }
        $batteryStates = ['EmptyBattery', 'LowBattery', 'BatteryOK'];
        if (!in_array($BatteryState, $batteryStates) || $Variable == '' || !$this->IsStringJsonEncoded($Variable)) {
            return;
        }
        $Variable = json_decode($Variable, true);
        foreach (json_decode($this->ReadPropertyString('ImmediateMailerNotification'), true) as $mailer) {
            if (!$mailer['Use']) {
                continue;
            }
            if ($mailer['ID'] <= 1 || @!IPS_ObjectExists($mailer['ID'])) {
                continue;
            }
            if (!$mailer['Use' . $BatteryState]) {
                continue;
            }
            $messageText = '';
            if ($BatteryState == 'EmptyBattery') {
                $messageText .= "Batterie leer:\n\n";
            }
            if ($BatteryState == 'LowBattery') {
                $messageText .= "Batterie schwach:\n\n";
            }
            if ($BatteryState == 'BatteryOK') {
                $messageText .= "Batterie OK:\n\n";
            }
            $lineText = $mailer[$BatteryState . 'MessageText'];
            $name = $this->GetValueFromMonitoredVariable($Variable['ID'], 'Name');
            if ($Variable['Comment'] != '') {
                $name = $name . ', ' . $this->GetValueFromMonitoredVariable($Variable['ID'], 'Comment');
            }
            //Check for placeholder
            if (strpos($lineText, '%1$s') !== false) {
                $lineText = sprintf($lineText, $name);
            }
            //Timestamp
            if ($mailer['Use' . $BatteryState . 'Timestamp']) {
                $lineText .= ', ' . $Variable['Timestamp'];
            }
            //Variable ID
            if ($mailer['Use' . $BatteryState . 'VariableID']) {
                $lineText .= ', ID: ' . $Variable['ID'];
            }
            //Battery type
            if ($mailer['Use' . $BatteryState . 'BatteryType']) {
                $batteryType = $this->GetValueFromMonitoredVariable($Variable['ID'], 'BatteryType');
                if ($batteryType != '') {
                    $lineText .= ', Batterietyp: ' . $batteryType;
                }
            }
            $messageText .= $lineText . "\n";
            $this->SendMail($mailer['ID'], $mailer['Subject'], $messageText);
            IPS_Sleep(100);
        }
    }
}