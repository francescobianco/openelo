# OpenElo – Design Document

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

## 8. Cosa OpenElo NON è

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
