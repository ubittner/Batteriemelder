# Batteriemelder

Zur Verwendung dieses Moduls als Privatperson, Einrichter oder Integrator wenden Sie sich bitte zunächst an den Autor.

Für dieses Modul besteht kein Anspruch auf Fehlerfreiheit, Weiterentwicklung, sonstige Unterstützung oder Support.  
Bevor das Modul installiert wird, sollte unbedingt ein Backup von IP-Symcon durchgeführt werden.  
Der Entwickler haftet nicht für eventuell auftretende Datenverluste oder sonstige Schäden.  
Der Nutzer stimmt den o.a. Bedingungen, sowie den Lizenzbedingungen ausdrücklich zu.


### Inhaltsverzeichnis

1. [Modulbeschreibung](#1-modulbeschreibung)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Schaubild](#3-schaubild)
4. [Auslöser](#4-auslöser)
5. [Benachrichtigungen](#5-benachrichtigungen)
6. [PHP-Befehlsreferenz](#6-php-befehlsreferenz)
   1. [Batteriestatus prüfen](#61-batteriestatus-prüfen)

### 1. Modulbeschreibung

Dieses Modul überwacht den Batteriestatus von Geräten in [IP-Symcon](https://www.symcon.de).

Die Überwachung oder Erstellung der Batterieliste wird immer durchgeführt.  
Wenn der Schalter `Aktiv` in WebFront auf `Aus` steht, werden lediglich keine Benachrichtigungen versendet.  

### 2. Voraussetzungen

- IP-Symcon ab Version 6.1

### 3. Schaubild

```
                      +------------------------+
                      | Batteriemelder (Modul) |
                      |                        |
Auslöser<-------------+ Status                 |
                      |                        |
                      +------------+-----------+
                                   |
                                   |                        +--------------------------+
                                   |                        | Benachrichtigung (Modul) |
                                   |                        |                          |
                                   +----------------------->| WebFront                 |
                                                            | Push Benachrichtigung    |
                                                            | SMS                      |
                                                            | E-Mail                   |
                                                            | Instant messaging        |
                                                            +--------------------------+
```

### 4. Auslöser

Das Modul Batteriemelder reagiert auf verschiedene Auslöser.
Wird der Status eines Auslösers aktualisiert, so werden alle aktivierten Variablen überprüft.

### 5. Benachrichtigungen

##### 5.1 Sofortige Benachrichtigung

##### 5.1.1 Gesamtstatus:

Ändert sich erstmalig der Gesamtstatus von `OK` auf `Alarm`,  
so werden die Benachrichtigungen für den Gesamtstatus `Alarm` versendet, sofern aktiviert.

Ändert sich der Gesamtstatus von `Alarm` wieder auf `OK`,  
so werden die Benachrichtigungen für den Gesamtstaus `OK` versendet, sofern aktiviert.

Ist in der Konfiguration `Maximal eine Benachrichtigung bis` aktiviert,  
so werden die Benachrichtigungen über die Änderung des jeweiligen Status maximal ***einmal*** versendet.

##### 5.1.2 Gerätestatus:

Ändert sich erstmalig der Gerätestatus von `OK` auf `Schwache Batterie` oder `Überfällige Aktualisierung`,  
so werden die Benachrichtigungen für den Gerätestatus `Schwache Batterie` oder `Überfällige Aktualisierung` versendet, sofern aktiviert.

Ändert sich Gerätestatus von `Schwache Batterie` oder `Überfällige Aktualisierung` wieder auf `OK`,  
so werden die Benachrichtigungen für den Gerätestatus `OK` versendet, sofern aktiviert.

Es werden Benachrichtigungen über die Änderung des jeweiligen Gerätestatus maximal ***einmal*** versendet.

#####  5.2 Tägliche Benachrichtigung

Eine Benachrichtigung über den Gesamtstaus und/oder Gerätestatus kann zu der festgelegten Zeit an den ausgewählten Tagen erfolgen.

#####  5.3 Wöchentliche Benachrichtigung

Eine Benachrichtigung über den Gesamtstaus und/oder Gerätestatus kann einmal wöchentlich zu der festgelegten Zeit erfolgen.

### 6. PHP-Befehlsreferenz

#### 6.1 Batteriestatus prüfen

```
boolean BATM_CheckBatteries(integer INSTANCE_ID);
```

Der Befehl liefert als Rückgabewert **TRUE**, wenn alle Batterien `OK` sind, andernfalls **FALSE**.

| Parameter     | Wert  | Bezeichnung    |
|---------------|-------|----------------|
| `INSTANCE_ID` |       | ID der Instanz |

Beispiel:  
> $result = BATM_CheckBatteries(12345);  
> echo $result;

