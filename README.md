# Sichtbarkeit der Backend-Symbole für Contao

[English version](README.en.md)

Seit Contao 5.5 stehen in den Backend-Listen nur noch wenige Symbole direkt in der Zeile (Bearbeiten, Veröffentlichen, Unterelemente). Kopieren, Verschieben, Löschen, Details, Versionen und weitere liegen im „…“-Menü und brauchen einen Klick mehr.

Mit diesem Bundle legst du in den Systemeinstellungen fest, welche Symbole wieder dauerhaft in der Zeile stehen: für alle Listen, je Bereich oder einfach alle wie in Contao 5.3. Benutzer können, wenn ihre Gruppe es erlaubt, im Profil eine eigene Auswahl treffen.

## Funktionen

- **Alle Symbole anzeigen:** jede Liste zeigt alle Symbole direkt in der Zeile, das „…“-Menü entfällt. Auch große Seitenbäume laden dann alle Symbole sofort.
- **Direkt in der Zeile anzeigen:** zuerst „In allen Listen“ für Symbole, die in jeder Liste sichtbar sein sollen, sofern es sie dort gibt, zum Beispiel „Details“. Das gilt auch für Listen anderer Erweiterungen. Darunter ergänzen zehn Bereiche die Auswahl für ihre Listen: Seiten, Artikel, Inhaltselemente, Nachrichten, Events, FAQ, Newsletter und Abonnenten, Formulare und Formularfelder, Dateien, Mitglieder.
- **Trotzdem im „…“-Menü lassen:** gleich aufgebaut und mit Vorrang. Damit geht etwa „Details überall, außer bei Mitgliedern“ oder „alle Symbole wie in Contao 5.3, nur Löschen bleibt im Menü“.
- **Neu nach/in:** die Schaltflächen zum Anlegen eines Elements nach oder in einem anderen lassen sich ebenfalls als Symbol einblenden.
- **Eigene Auswahl je Benutzer:** In der Benutzergruppe lässt sich „Eigene Symbolauswahl erlauben“ setzen. Dann können ihre Mitglieder im Profil eine eigene Auswahl einschalten, die die Vorgabe ersetzt. Sie ist beim Einschalten mit der Vorgabe vorbefüllt und zeigt nur Bereiche, deren Backend-Module der Benutzer nutzen darf. Administratoren dürfen das immer.
- Zur Auswahl stehen nur Symbole, die es in der jeweiligen Liste wirklich gibt, mit den Contao-eigenen Bezeichnungen. Bereiche nicht installierter Erweiterungen (etwa Nachrichten oder FAQ) entfallen.

Ohne Auswahl ändert das Bundle nichts. Die Hauptsymbole, die Contao selbst immer zeigt, bleiben immer sichtbar. Ein Rechtsklick auf eine Zeile öffnet weiterhin das vollständige Menü.

## Voraussetzungen

- Contao 5.7 mit PHP 8.3 oder neuer
- Contao 6.0 mit PHP 8.4 oder neuer

## Installation

```bash
composer require mandrael/contao-backend-icon-visibility
```

Oder im Contao Manager nach `mandrael/contao-backend-icon-visibility` suchen. Danach die Datenbank aktualisieren (Contao Manager oder `vendor/bin/contao-console contao:migrate`): Das Bundle legt Felder für die eigene Auswahl in den Tabellen der Benutzer und Benutzergruppen an.

## Einrichtung

Backend → System → Einstellungen → Abschnitt **Backend-Symbole**. Die Werte werden wie die übrigen Systemeinstellungen gespeichert und gelten für alle Benutzer ohne eigene Auswahl.

Eigene Auswahl: Benutzerverwaltung → Benutzergruppen → Abschnitt **Backend-Symbole** → „Eigene Symbolauswahl erlauben“. Die Benutzer finden die Einstellung dann in ihrem Profil.

## Hinweise

- Viele Symbole brauchen Platz: in schmalen Fenstern können lange Titel gekürzt werden.
- Mit „Alle Symbole anzeigen“ rendert Contao bei sehr großen Listen alle Symbole sofort statt auf Abruf. Das kostet bei mehreren hundert Zeilen etwas Ladezeit, ungefähr wie in Contao 5.3.

## Lizenz

MIT, siehe [LICENSE](LICENSE).
