# AachenerRegistrationGuard 1.0.0

Zusatz-Plugin für plentyShop LTS: Neue Registrierungen werden nur mit der in der
Plugin-Konfiguration eingetragenen E-Mail-Domain zugelassen. Standard ist
`aachener-grund.de`.

Der Filter prüft serverseitig vor dem Registrierungs-Controller. Bei einer
fremden oder ungültigen E-Mail-Adresse erscheint im vorhandenen Formular:

> Bitte verwenden Sie Ihre geschäftliche E-Mail-Adresse mit der Endung @aachener-grund.de.

Die Endung in der Meldung folgt automatisch der konfigurierten Domain.

## Installieren mit plentyDevTool

1. Eine Kopie des Aachener-Plugin-Sets für den ersten Build und Test verwenden.
2. plentyDevTool mit eurem Plenty-System verbinden und das betreffende Set
   synchronisieren. Das Tool legt die Struktur `System_<PID>/Set_<ID>/` an.
3. Den vollständigen Ordner **AachenerRegistrationGuard** aus diesem Paket in
   diesen `Set_<ID>`-Ordner kopieren. Der Ordnername muss exakt dem Namen in
   `plugin.json` entsprechen. Keinen zusätzlichen ZIP-Ordner dazwischen einfügen.
4. In plentyDevTool **Detect new local plugins / Neue lokale Plugins erkennen**
   (`Strg+D`) und anschließend **Install** wählen. Die neuen Dateien per
   **Push** (`Strg+U`) übertragen.
5. In Plenty unter **Plugins → Plugin-Set-Übersicht** das entsprechende Set öffnen
   und **AachenerRegistrationGuard** aktivieren.
6. Im neuen Plugin **Konfiguration → Registrierung → Erlaubte E-Mail-Domain**
   aufrufen, `aachener-grund.de` eintragen und speichern.
7. Das gesamte Plugin-Set vollständig bereitstellen und die unten beschriebenen
   Formularprüfungen durchführen. Danach im Aachener-Live-Set installieren,
   konfigurieren, aktivieren und vollständig bereitstellen.

Alternativ lässt sich der INHALT dieses Plugin-Ordners in einem Git-Repository
bereitstellen (`plugin.json` direkt im Repository-Hauptverzeichnis). Das Repository
kann über **Plugins → Git** mit Plenty verbunden werden. Es wurde hier kein
Remote-Repository angelegt. Das ZIP ist ein Quellcodepaket; ein allgemeiner
ZIP-Upload im ShopBuilder ist keine Plugin-Installation.

## Konfiguration

Es gibt genau ein Fachfeld: **Erlaubte E-Mail-Domain**.

- `aachener-grund.de` und `@aachener-grund.de` werden akzeptiert.
- Groß-/Kleinschreibung der Domain ist unerheblich.
- Es wird eine einzelne Domain ohne URL, Wildcard oder Liste erwartet.
- Eine leere oder ungültige Einstellung blockiert die Registrierung (HTTP 503),
  statt versehentlich beliebige Adressen zu erlauben.
- Ist der Konfigurationswert noch nicht gespeichert, greift der mitgelieferte
  Standard `aachener-grund.de`.
- Die Regel gilt im aktiven Plugin-Set. Wenn mehrere Shops dasselbe Set verwenden,
  gilt sie für diese Shops gemeinsam. Deshalb nur im gewünschten Set aktivieren.
- Zum Abschalten das Zusatz-Plugin deaktivieren und das Set erneut bereitstellen.

## Verhalten

| Eingabe bei Domain `aachener-grund.de` | Ergebnis |
| --- | --- |
| `name@aachener-grund.de` | zugelassen |
| `name@AACHENER-GRUND.DE` | zugelassen |
| `name+team@aachener-grund.de` | zugelassen |
| `name@gmail.com` | abgewiesen |
| `name@sub.aachener-grund.de` | abgewiesen |
| `name@aachener-grund.de.example` | abgewiesen |
| fehlende oder beschädigte E-Mail-Daten | abgewiesen |

Geprüft wird die Login-Adresse des Kontakts, nicht die Rechnungsadresse. Bei
gültiger Eingabe läuft der vorhandene Registrierungsprozess ohne Änderung weiter.
Das Plugin erstellt keine eigenen Kontakte, versendet keine E-Mails und schreibt
keine Passwörter oder E-Mail-Adressen in Logs.

## Abdeckung und Grenzen

- Normale LTS-Registrierung: `POST /rest/io/customer`.
- B2B-Shop-Registrierung: `POST /rest/b2b/customer`. Dieser Endpunkt und das
  Kontakt-Datenformat wurden am 09.10.2026 im öffentlich ausgelieferten
  B2B-JavaScript eures Shops festgestellt.
- Die Prüfung nutzt `contact.options` mit `typeId=2` und `subTypeId=4`
  (Login-E-Mail) sowie gegebenenfalls den gleichlautenden Alias `contact.email`.
  Widersprüchliche Adressen und doppelte Login-Optionen werden abgelehnt.
- Login, Passwort-Zurücksetzen, bestehende Konten, Adressänderungen, Gastkauf und
  administrative REST-Kontaktanlage gehören nicht zu diesem Registrierungsfilter.
- Weitere Plugins mit anderen Registrierungsendpunkten müssen zusätzlich
  angebunden werden. Bei einem Update des B2B-Plugins dessen Formular erneut testen.
- Diese Version enthält ausschließlich die zuletzt gewünschte Domainprüfung.
  E-Mail-Bestätigung, Nachweis des Postfachzugriffs und Kundenklassenwechsel sind
  nicht Bestandteil. Eine erlaubte Domain allein bestätigt keinen Postfachbesitz.

## Prüfung in Plenty

Die lokale Prüfung ersetzt nicht den vollständigen Build und Formular-Test in
eurem Plenty-System; beides konnte ohne Systemzugriff noch nicht ausgeführt werden.

1. Mit einer fremden Testadresse das B2B-Formular absenden: Fehlermeldung erscheint,
   in **CRM → Kontakte** wurde kein Kontakt für diese Adresse angelegt.
2. Gleiches im normalen Registrierungsformular `/register/` und gegebenenfalls
   dessen Overlay prüfen. Erwartet: Ablehnung auf beiden Wegen.
3. Mit einer dafür vorgesehenen Firmen-Testadresse registrieren: Der vorhandene
   Ablauf funktioniert. Dieser positive Test legt tatsächlich einen Kontakt an.
4. Eine bestehende Anmeldung und den Checkout prüfen.
5. Domain in der Testkonfiguration ändern, speichern und bereitstellen: Nur die
   neu konfigurierte Domain muss zugelassen sein.

Eine abgelehnte Anfrage antwortet mit JSON, HTTP 422 und dem Feld
`error.message`. Dieses Format entspricht der Fehlerbehandlung beider Formulare.
Falls eine Shop-Anpassung die normalen Fehlermeldungen ausblendet, muss deren
Anzeige zusätzlich aktiviert werden; die serverseitige Ablehnung bleibt bestehen.

## Technische Hinweise

Der ServiceProvider registriert eine globale Plenty-Middleware. Sie prüft nur
POST-Anfragen an die beiden Registrierungsendpunkte, bevor deren Controller laufen.
Eine negative Entscheidung beendet die Anfrage nach dem Senden der Fehlerantwort
explizit. Plenty dokumentiert für `Middleware::before()` keinen Rückgabewert zum
Abbrechen. Ein bloßes `return $response` wäre deshalb hier nicht ausreichend.
Das Beenden per `exit` entspricht dem Prinzip des offiziellen IO-Guards.

Keine Überschreibung von Marketplace-Dateien, keine neuen Routen, keine Migrationen,
kein Composer-Paket und keine Container-Verknüpfung sind erforderlich.

## Quellen der Schnittstellenprüfung

- [Plugin-Aufbau und Pflichtfelder](https://developers.plentymarkets.com/en-gb/developers/main/plugin-definition.html)
- [Plugin-Konfiguration](https://developers.plentymarkets.com/en-gb/developers/main/plugin-configuration/how-to-plugin-configuration.html)
- [Middleware-Schnittstellen](https://developers.plentymarkets.com/en-gb/interface/stable7/Miscellaneous.html)
- [IO CustomerResource](https://github.com/plentymarkets/plugin-io/blob/stable/src/Api/Resources/CustomerResource.php)
- [IO AbstractGuard](https://github.com/plentymarkets/plugin-io/blob/stable/src/Guards/AbstractGuard.php)
- [LTS Registration.vue](https://github.com/plentymarkets/plugin-ceres/blob/stable/resources/js/src/app/components/customer/Registration.vue)
- [plentyDevTool: lokale Plugins hinzufügen](https://developers.plentymarkets.com/en-gb/plentydevtool/main/plentydevtool-guide.html#_add_local_plugins)
- [Plugins über Git hinzufügen](https://knowledge.plentyone.com/de-de/manual/main/plugins/plugins-system-hinzufuegen.html)
