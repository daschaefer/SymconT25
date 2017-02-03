Mobotix T24/T25 PHP Module für IP-Symcon
===
Dieses IP-Symcon PHP Modul dient zur Integration einer Mobotix Gegensprechanlage des Typs T24 oder T25.
Außerdem werden applikationsweite Methoden zur Steuerung der Funktionalität der Gegensprechanlage bereitgestellt.
Es werden automatisch Variablen anhand des Event Typs der Gegensprechanlage angelegt. Mit diesen Variablen kann dann z.B. durch Ereignisse welche auf diesen Variablen beruhen weiter gearbeitet werden.

**Content**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Anforderungen](#2-anforderungen)
3. [Vorbereitung & Installation & Konfiguration](#3-vorbereitung--installation--konfiguration)
4. [Variablen](#4-variablen)
5. [Hintergrund Skripte](#5-hintergrund-skripte)
6. [Funktionen](#6-funktionen)
6. [Webhook Parameter](#7-webhook-parameter)

## 1. Funktionsumfang  
Die folgenden Funktionalitäten sind implementiert:
- Automatisches Anlegen des Kamerastreams
- Automatisches erfassen der in der Kamera abonnierten Events
  - mit Zeitstempel
- Tür Summer betätigen (aus Webfront oder Programmierung)

## 2. Anforderungen

- IP-Symcon 4.x installation (Linux / Windows)
- Fertig installierte und ins Netzwerk integrierte Mobotix Gegensprechanlage des Typs T24 oder T25

## 3. Vorbereitung & Installation & Konfiguration

### Installation in IPS 4.x
Im "Module Control" (Kern Instanzen->Modules) die URL "git://github.com/daschaefer/SymconT25.git" hinzufügen.  
Danach ist es möglich eine neue Mobotix T25 Instanz innerhalb des Objektbaumes von IP-Symcon zu erstellen.

### Konfiguration innerhalb IPS
**IP-Adresse:**

*Die IP-Adresse/Hostname unter der die Gegensprechanlage erreichbar ist.*

**Port:**

*Der Port unter die Gegensprechanlage erreichbar ist. Dieser ist in der Regel der Port 80 und muss nicht geändert werden.*

**Benutzername: (optional)**

*Der Benutzername der auf die HTTP API Funktionen der Gegensprechanlage zugreifen darf.*

**Passwort: (optional)**

*Das Passwort zum Benutzernamen.*

**Protokoll:**

*Das verwendete Protokoll (HTTP oder HTTPS) vom Webinterface der Gegensprechanlage*

**Webhook Benutzername:**

*Der Benutzername zum Schutz des Webhooks. Standardmäßig: t25*

**Webhook Passwort:**

*Das Passwort zum Schutz des Webhooks. Wird automatisch bei Installation der Instanz generiert und muss in der Regel nicht geändert werden.*

**Webhook Debugging:**

*Gibt alle von der Sprechanlage übergebenen Parameter an den Webhook als Array aus.*

**Kamerabilder Ordner: (optional)**

*Der Ordner auf den die Gegensprechanlage bei einem Ereignis Bilder ablegt. Idealerweise handelt es sich hier um eine Freigabe per FTP auf dem Symcon Server unterhalb dem Ordner webfront/users. In diesem Bilder Ordner dürfen sich keine weiteren Ordner befinden.
Die Gegensprechanlage sollte nur Bilder in dem Verzeichnis ablegen. Der Dateiname sollte so gewählt werden das er sortierbar ist, Empfehlung: ```$(TMS.YEAR)_$(TMS.MON)_$(TMS.DAY)-$(TMS.HOUR)_$(TMS.MIN)_$(TMS.SEC).jpg```

**Kamerabilder Anzahl: (optional)**

*Die Anzahl der Bilder die maximal Angezeigt werden. Im Standard: die Bilder zu den letzten 5 Ereignissen.*

**Ereignisprotokollierung:**

*Wenn der Haken gesetzt ist werden alle an IP-Symcon weitergeleiteten Events (Ereignisse) von der Gegensprechanlage innerhalb IP-Symcon im Log aufgezeichnet.*

**Ereignisprotokoll mit Timestamp:**

*Nur Sinnvoll wenn Ereignisprotokollierung aktiviert wurde. Fügt zu dem Protokolleintrag ein Datums und Zeitstempel hinzu.*

### Konfiguration der Gegensprechanlage

1. Webfrontend der Gegensprechanlage öffnen und als Administrator anmelden

1. Admin Menü -> Profile für Netzwerkmeldungen öffnen und neues Profil anlegen (IP-Adresse und ggfs. Port anpassen):

![Profile für Netzwerkmeldungen](images/1_Profile_für_Netzwerkmeldungen.png?raw=true "Profile für Netzwerkmeldungen")

1. Setup Menü -> Aktionsgruppen-Übersicht öffnen und eine neue Aktionsgruppe anlegen:
	- Wichtig: Die hier abonnierten Ereignisse werden an IP-Symcon gemeldet!

![Aktionsgruppe](images/2_Aktionsgruppe.png?raw=true "Aktionsgruppe")

## 4. Variablen
**letzte Aktivität**

*Enthält das letzte Ereignis samt dem Zeitstempel des auftretens.*

**Türsummer**

*Mit dieser Variable kann der Türsummer betätigt werden.*

**T25 Kamera Stream**

*Dieses Medien Objekt enthält die URL zur Kamera der Gegensprechanlage und kann entsprechend ins Webfrontend eingebunden werden.
Achtung: Chrome hat Probleme mit der Basis Authentifizierung sobald Benutzername + Passwort gesetzt sind. Es wird daher kein Bild angezeigt => Browser wechseln. Firefox hat keine Probleme.*

**Gespeicherte Kamerabilder anzeigen**

*Dieses PopUp Objekt enthält eine String Variable mit den letzten 5 Ereignisbildern. Die Anzahl der Bilder kann in der Konfiguration verändert werden.*

**Sensorvariablen**

*Diverse Sensorvariablen welche von der Kamera bereitgestellt werden, z.B. Helligkeit, Temperatur etc.*

## 5. Skripte

**Hook**

*Dieses Skript dient als Brücke zwischen dem automatisch angelegten Webhook und der Implementierung innerhalb des Moduls.*

## 6. Funktionen

```php
T25_GetLastEvent(integer $InstanceID)
```
Gibt ein Array mit folgendem Inhalt zurück:
```
stdClass Object
(
    [event] => Bell
    [timestamp] => 29.03.2016 19:34:34
    [unix_timestamp] => 1459272874
)
```

---
```php
T25_HangUp(integer $InstanceID)
```
Beendet alle VoIP Anrufe. Praktisch z.B. um das Melden per VoIP zu beenden wenn die Tür nach einem Klingeln geöffnet wurde.

---
```php
T25_OpenDoor(integer $InstanceID)
```
Öffnet die Tür mittels des Türsummers.

---
```php
T25_PlaySoundFile(string $FileName)
```
Spielt eine zuvor hochgeladene Sounddatei über die Lautsprecher der Kamera ab. Der Dateiname muss exact so übergeben werden wie er in der Kamera abgelegt wurde (Case Sensitive).

---
```php
T25_ProcessHookData(integer $InstanceID)
```
Verabeitet die Informationen des Webhooks.

---
```php
T25_UpdateData(integer $InstanceID)
```
Synchonisiert Daten von der Kamera zu IP-Symcon.

## 7. Webhook Parameter
```html
event
```
Die Bezeichnung des Ereignisses, welches innerhalb Symcon als Variable gespeichert wird und mit einem Unix Timestamp des letzten Auftretens gefüllt wird.

---
```html
instanceid
```
Wird vorbelegt mit der ID der Instanz innerhalb Symcons. Dadurch kann der Webhook die Zuordnung bei mehreren eingesetzten Sprechanlagen übernehmen und setzt die Variablen in den richtigen Instanzen.

