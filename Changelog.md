# Changelog
### Version 2.6
Feature: Ereignisbilder sind nun auch von einem separaten Webserver ladbar. Dazu muss das File /libs/ext_http/index.php in das Verzeichnis gelegt werden wo die Ereignisbilder gespeichert werden. Anschließend kann die Konfiguration entsprechend im Modul vorgenommen werden.

### Version 2.4
Bugfix: Anpassung der Modulstruktur aufgrund IP-Symcon Richtlinien.

### Version 2.3
Feature: Debugging Möglichkeit für den Webhook.
Bugfix: Sortierung der letzten Ereignisse. Dazu muss die Abspeicherung der Bilder innerhalb der Kamera geändert werden => Siehe Doku.

### Version 2.2
Feature: Ausgabe einer zuvor hochgeladenen Audiodatei über die Lautsprecher der Kamera. Aufruf via T25_PlaySoundFile(string $FileName).  

### Version 2.1
Change: Zusammenhängende Ereignisse werden nun getrennt und als einzelnes Ereignis gespeichert.
Feature: Komisches Sonderzeichen bei der Übergabe von der T25 wird nun rausgefiltert.   

### Version 2.0
Feature: Bei dem Aufruf des Webhooks kann nun ein Parameter 'instanceid=' mitgegeben werden, anhand dessen wird die Datenübernahme der angegebenen Instanz geändert. Dies wird z.B. in einer Umgebung Verwendung finden in derer mehrere T24/T25 Sprechanlagen vorhanden sind. 

### Version 1.9
Change: Letztes Ereignis mit oder ohne Timestamp nun per Checkbox einstellbar.

### Version 1.8
Change: Letztes Ereignis wird ohne Timestamp erfasst, damit man dies bei einer Archivierung nicht doppelt speichert.
Bugfix: Sortierung der letzten Kamerabilder nach Last Modified Date und nicht mehr nach Ordnernamen.

### Version 1.7
Bugfix: Mehrfachanlage von Kameravariablen wenn Sonderzeichen im Bezeichner vorhanden sind.

### Version 1.6
Bugfix: Mehrfachanlage von Ereignisvariablen wenn Sonderzeichen im Bezeichner vorhanden sind.

### Version 1.5
Feature: Benutzername + Passwort Authentifikation gegenüber der Gegensprechanlage.
Feature: LED's der Gegensprechanlage ein/aus-schaltbar.
Feature: Die gespeicherten Bilder der letzten Ereignisse anzeigen.
Feature: Sensordaten (Temperatur, Helligkeit etc.) der Kamera werden ausgelesen und in Variablen gespeichert.
 
### Version 1.4
Feature: VoIP Anrufe können nun mit der Funktion T25_Hangup() beendet werden.

### Version 1.3
Feature: Webhook nun mit Authentifizierung. Benutzername ist im Standard: t25, das Passwort word automatisch generiert und ist in den Einstellungen der Instanz einseh und editierbar.

### Version 1.2
Bugfix Release.

### Version 1.0
Erstes Release des Moduls mit Basisfähigkeiten.

