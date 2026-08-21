# OpenElo – Design Document

> **Nota di missione (agosto 2026).**
> Il progetto cambia missione: da sistema di rating decentralizzato a
> **piattaforma per la gestione di corsi e scuole di scacchi**.
> L'utente principale è **l'istruttore indipendente**.
> Il documento originale sul sistema di rating è conservato integralmente in
> [Appendice A](#appendice-a--missione-precedente-sistema-di-rating): non è
> stato eliminato nulla, né dai documenti né dal codice.

---

## 1. Visione (missione attuale)

**OpenElo** è una piattaforma per **istruttori di scacchi indipendenti e scuole di scacchi**.

L'obiettivo è togliere di mezzo la burocrazia dell'insegnamento:

* l'istruttore si registra da solo, in autonomia (email + password)
* crea le proprie **classi** (corsi)
* carica i propri **studenti** e li iscrive alle classi
* segue il percorso e i progressi di ogni studente

Il principio chiave è:

> **l'istruttore è il proprietario dei suoi dati: le sue classi, i suoi studenti.**

Nessuna federazione, nessun circolo obbligatorio, nessun costo di avvio.

---

## 2. Utente principale

L'**istruttore indipendente**:

* insegna scacchi per conto proprio o dentro una scuola/accademia
* ha un account vero (registrazione con email e password)
* vede **solo** le proprie classi e i propri studenti (isolamento totale per istruttore)
* può indicare il nome della sua scuola/accademia nel profilo

In futuro: scuole con più istruttori, ruoli, genitori con accesso in lettura.

---

## 3. Concetti fondamentali (missione attuale)

### 3.1 Istruttore (`instructors`)

Account della piattaforma. Registrazione libera, conferma email (non bloccante:
l'account è già usabile, il link serve a verificare l'indirizzo), reset password.

### 3.2 Classe (`classes`)

Un corso tenuto da un istruttore: nome, livello, sede, orario, date di inizio/fine,
numero massimo di studenti, stato (`active` / `archived`).

### 3.3 Studente (`students`)

Una persona seguita dall'istruttore: anagrafica, contatti, contatti del genitore
per i minorenni, livello, rating, note.
Uno studente appartiene a **un** istruttore e può frequentare **più classi**.

### 3.4 Iscrizione (`class_students`)

Il legame studente ↔ classe. Le rimozioni sono "soft" (`status = 'left'`):
lo storico delle iscrizioni non viene mai perso.

---

## 4. Modello dati (missione attuale)

```
instructors ──< classes ──< class_students >── students >── instructors
     │                                                          │
     └──< instructor_tokens (verifica email, reset password)     │
                                                                 │
students.player_id ─ ─ ─ (ponte opzionale verso il modello legacy `players`)
```

Ogni query dell'area scuola è filtrata per `instructor_id`: un istruttore non
può in nessun caso vedere o modificare i dati di un altro.

---

## 5. Rapporto con il sistema di rating legacy

Il sistema di rating (circuiti, circoli, giocatori, partite, Elo) **non è stato
rimosso**: codice, tabelle e pagine sono intatti e ancora raggiungibili via URL
(`?page=circuits`, `?page=home_rating`, ecc.), semplicemente non sono più
collegati nella navigazione principale.

L'ipotesi di riuso è precisa:

| Legacy            | Scuola                                           |
|-------------------|--------------------------------------------------|
| circuito          | rete di scuole che si riconoscono a vicenda      |
| società / circolo | la scuola dell'istruttore                        |
| giocatore         | studente promosso a giocatore con rating         |
| partita validata  | partita interna al corso / torneo di classe      |

Il campo `students.player_id` è già predisposto come ponte fra i due mondi.

---

## 6. Lingua

Il multilingua (IT/EN) resta una feature completa: tutte le traduzioni inglesi
sono mantenute. Per il lancio però la lingua è **forzata a italiano** via
`FORCE_LANG` (`src/config.php`), e il selettore di lingua è nascosto.
Per riattivare il multilingua basta impostare `FORCE_LANG=''`.

---

## 7. Roadmap (da definire progressivamente)

Prossime funzioni candidate, in ordine di utilità per l'istruttore:

* lezioni e calendario del corso
* registro presenze
* pagamenti / quote di iscrizione
* materiale didattico ed esercizi assegnati
* progressi e valutazioni per studente
* comunicazioni ai genitori
* tornei interni alla scuola (aggancio al motore Elo legacy)

---

---

# Appendice A — Missione precedente (sistema di rating)

> Tutto quanto segue descrive la missione originale del progetto.
> È conservato per riferimento: il codice corrispondente è ancora nel repository
> e funzionante.

## 1. Visione

**OpenElo** è un sistema open-source di rating scacchistico basato su Elo, pensato per tornei e partite **fuori dal circuito FIDE**, con l’obiettivo di:

* ridurre drasticamente i costi organizzativi
* mantenere credibilità statistica
* evitare un’autorità centrale
* fondarsi su **fiducia locale (circuiti)** e **validazione via email**

Il principio chiave è:

> **il rating non è globale: vale solo all’interno di un circuito di fiducia**.

---

## 2. Concetti fondamentali

### 2.1 Circuito

Un **circuito** è un perimetro di fiducia creato dagli utenti.

* rappresenta una comunità (es. più circoli che collaborano)
* definisce dove il rating è valido
* funge da equivalente leggero di una federazione

Il trust non è nei singoli, ma nel **circuito**.

---

### 2.2 Società (Circolo)

Una **società**:

* è creata da un presidente (email)
* aderisce a uno o più circuiti
* fornisce un validatore umano (il presidente)

---

### 2.3 Giocatore

Un **giocatore**:

* è una persona fisica
* viene creato con nome, cognome ed email
* può appartenere a più circuiti
* ha **un rating distinto per ogni circuito**

---

### 2.4 Rating

* Il rating è basato su **Elo classico**
* Non esiste un rating globale
* Ogni coppia (giocatore, circuito) ha il proprio Elo

Questo riflette il fatto che Elo misura **relazioni locali**, non abilità assoluta.

---

## 3. Principio di fiducia

OpenElo evita il problema del consenso ingenuo introducendo:

* identità persistenti (email)
* validazione da terza parte (presidente di società)
* perimetri di fiducia (circuiti)

Una partita è valida **solo se certificata**.

---

## 4. Workflow basato su email

Tutto il sistema è progettato come **email-first**, senza login complessi.

### 4.1 Creazione circuito

1. Utente inserisce nome circuito + email
2. Email di conferma
3. Circuito attivo

---

### 4.2 Creazione giocatore

1. Inserimento nome, cognome, email
2. Email di conferma
3. Giocatore attivo

---

### 4.3 Creazione società

1. Inserimento nome società + email presidente
2. Email di conferma al presidente
3. Società attiva

---

### 4.4 Adesione società a circuito

1. La società richiede l’adesione a un circuito
2. Email al proprietario del circuito
3. Conferma → società ammessa al circuito

Questo sostituisce l’affiliazione federale tradizionale.

---

### 4.5 Inserimento e validazione partite

Per registrare una partita servono:

* giocatore A
* giocatore B
* società
* circuito
* risultato

Workflow:

1. Inserimento risultato
2. Invio email a:

    * giocatore A
    * giocatore B
    * presidente della società
3. **Tripla conferma**
4. La partita diventa certificata
5. Aggiornamento Elo

Senza tre conferme → la partita **non esiste**.

---

## 5. Modello dati (concettuale)

Entità principali:

* circuits
* clubs
* players
* circuit_club (adesioni)
* matches
* ratings (player × circuit)
* confirmations (workflow unificato)

Tutte le conferme (creazioni, adesioni, partite) usano **la stessa tabella di confirmation**.

---

## 6. Calcolo del rating

### 6.1 Formula

Elo classico:

* Expected score standard
* Aggiornamento simmetrico

---

### 6.2 Fattore K (stile FIDE)

Regola ufficiale OpenElo:

* **K = 40** → meno di 30 partite nel circuito
* **K = 20** → rating < 2200
* **K = 10** → rating ≥ 2200

Caratteristiche:

* identico al feeling FIDE
* determinato **per circuito**
* nessun moltiplicatore esotico

---

## 7. Sicurezza e anti-abuso

Il sistema è protetto da:

* costo sociale della validazione (presidenti)
* isolamento dei circuiti
* rating non trasferibile
* append-only delle partite

Un circuito finto produce rating senza valore esterno.

La reputazione emerge naturalmente.

---

## 8. Cosa OpenELO NON è

* non è un rating FIDE
* non genera titoli
* non pretende di misurare la forza assoluta

È uno strumento per:

* seeding
* ranking locali
* tornei amatoriali
* circuiti indipendenti

---

## 9. Filosofia del progetto

OpenElo è:

* federativo ma bottom-up
* decentralizzato ma ordinato
* semplice ma matematicamente onesto

> **La fiducia non è imposta: è scelta.**

---

## 10. Sintesi finale

OpenElo è un **ecosistema di rating Elo locali**, governati da comunità, certificati via email, senza autorità centrale e con regole chiare.

Un’alternativa leggera, credibile e moderna ai circuiti federali tradizionali.
