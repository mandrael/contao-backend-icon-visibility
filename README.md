# Sichtbarkeit der Backend-Symbole für Contao

[English version](README.en.md)

Seit Contao 5.5 stehen in den Backend-Listen nur noch wenige Symbole direkt in der Zeile (Bearbeiten, Veröffentlichen, Unterelemente). Kopieren, Verschieben, Löschen, Details, Versionen und weitere liegen im „…“-Menü und brauchen einen Klick mehr.

Mit diesem Bundle legst du in den Systemeinstellungen fest, welche Symbole wieder dauerhaft in der Zeile stehen: für alle Listen, je Bereich oder einfach alle wie in Contao 5.3.

## Funktionen

- **Alle Symbole anzeigen:** jede Liste zeigt alle Symbole direkt in der Zeile, das „…“-Menü entfällt. Auch große Seitenbäume laden dann alle Symbole sofort.
- **In allen Listen:** Symbole, die in jeder Liste sichtbar sein sollen, sofern es sie dort gibt, zum Beispiel „Details“. Das gilt auch für Listen anderer Erweiterungen.
- **Je Bereich zusätzlich:** Seiten, Artikel, Inhaltselemente, News, Events, Dateien, Formulare (mit Formularfeldern) und Mitglieder. Bei Seiten etwa „Artikel“ oder „Rekursiv kopieren“.
- **Neu nach/in:** die Schaltflächen zum Anlegen eines Elements nach oder in einem anderen lassen sich ebenfalls als Symbol einblenden.
- Zur Auswahl stehen nur Symbole, die es in der jeweiligen Liste wirklich gibt, mit den Contao-eigenen Bezeichnungen.

Ohne Auswahl ändert das Bundle nichts. Die Hauptsymbole, die Contao selbst immer zeigt, bleiben immer sichtbar. Ein Rechtsklick auf eine Zeile öffnet weiterhin das vollständige Menü.

## Voraussetzungen

- Contao 5.7 mit PHP 8.3 oder neuer
- Contao 6.0 mit PHP 8.4 oder neuer

## Installation

```bash
composer require mandrael/contao-backend-icon-visibility
```

Oder im Contao Manager nach `mandrael/contao-backend-icon-visibility` suchen. Eine Datenbankänderung ist nicht nötig.

## Einrichtung

Backend → System → Einstellungen → Abschnitt **Backend-Symbole**. Die Werte werden wie die übrigen Systemeinstellungen gespeichert und gelten für alle Benutzer.

## Hinweise

- Viele Symbole brauchen Platz: in schmalen Fenstern können lange Titel gekürzt werden.
- Mit „Alle Symbole anzeigen“ rendert Contao bei sehr großen Listen alle Symbole sofort statt auf Abruf. Das kostet bei mehreren hundert Zeilen etwas Ladezeit, ungefähr wie in Contao 5.3.

## Lizenz

MIT, siehe [LICENSE](LICENSE).
