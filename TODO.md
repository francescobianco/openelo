# TODO

- Introdurre lo stile di circuito, nel circuito si potra camviare lo stile questo camviere come si genera la classifica e altri comportamenti particolari

leggere queste email per le ipotesi di stili di circuito:
 la ladder proposta da Richard Hendin
 il knockout proposto da James chesswhitstable@gmail.com
---

# TODO — Piattaforma corsi e scuole di scacchi

Missione attuale (vedi `DESIGN.md`): piattaforma per istruttori di scacchi
indipendenti e scuole di scacchi. Fatto finora: registrazione istruttore,
login/logout, verifica email, reset password, dashboard, classi, studenti,
iscrizioni.

Da definire / prossimi passi:

- [ ] Lezioni e calendario del corso
- [ ] Registro presenze
- [ ] Pagamenti e quote di iscrizione
- [ ] Materiale didattico ed esercizi assegnati
- [ ] Progressi e valutazioni per studente
- [ ] Comunicazioni ai genitori (email già predisposta: `sendStudentClassInvite`)
- [ ] Tornei interni alla scuola, agganciati al motore Elo legacy
- [ ] Riscrivere la pagina "Chi Siamo" (`src/pages/about.php`) sulla nuova missione:
      per ora è scollegata dal menu principale perché racconta ancora il sistema di rating
- [ ] Scuole con più istruttori (ruoli, permessi)
- [ ] Riattivare il multilingua quando andremo oltre l'Italia (`FORCE_LANG=''`)
