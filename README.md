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

**Protokoll:**

*Das verwendete Protokoll (HTTP oder HTTPS) vom Webinterface der Gegensprechanlage*

**Ereignisse protokollieren:**

*Wenn der Haken gesetzt ist werden alle an IP-Symcon weitergeleiteten Events (Ereignisse) von der Gegensprechanlage innerhalb IP-Symcon im Log aufgezeichnet.*

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

*Dieses Medien Objekt enthält die URL zur Kamera der Gegensprechanlage und kann entsprechend ins Webfrontend eingebunden werden.*

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
T25_OpenDoor(integer $InstanceID)
```
Öffnet die Tür mittels des Türsummers.

---
```php
T25_ProcessHookData(integer $InstanceID)
```
Verabeitet die Informationen des Webhooks.
