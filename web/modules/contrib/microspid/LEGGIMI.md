MICROSPID PASW
--------------
Microspid è un modulo Drupal 8 che implementa il protocollo SPID senza l'ausilio di librerie esterne,
includendo tutto ciò che è strettamente necessario per far funzionare Drupal come Service Provider
SPID e puntando alla facilità d'uso e manutenzione.

PREMESSE
--------
1) dopo la generazione del certificato fate in luogo sicuro e protetto una copia della cartella cert,
questa cartella non andrà mai cancellata fin quando si intende utilizzare il SP. Se preferite, potete
spostare i file spid-sp.pem/crt in una cartella fuori dal web e inserire nella configurazione il nuovo
percorso assoluto della cartella.
2) conservare il contenuto della tabella del db drupal denominata microspid_tracking. Questa tabella
contiene il tracciamento di ogni sessione di autenticazione. Ogni due giorni una routine cron si occupa
di cancellare le righe incomplete e quelle presenti da più di 24 mesi, secondo le regole SPID.
3) Le implementazioni multisito sono possibili, anche se richiedono un intervento a mano sul template
dei metadati.

INSTALLAZIONE E CONFIGURAZIONE
------------------------------
L'installazione non dovrebbe porre problemi, è sufficiente seguire la procedura standard in questi casi.
Il pacchetto zip o tarball va scaricato da https://drupal.org/project/microspid .
Una volta installato il modulo entrate nella pagina di configurazione (admin->configurazione->microspid)
e cliccate su "crea certificato". Inserite i dati della vostra pubblica amministrazione e procedete alla
creazione del certificato; verrete riportati alla pagina di configurazione, però non attivate subito l'au-
tenticazione; invece verificate che alla pagina https://<miosito.it>/microspid_metadata si possa scaricare il
file metadata.xml contenente i metadati per gli IdP SPID. Potete a questo punto avviare la procedura Agid
per la verifica dei metadati e il deploy agli IdP (trovate le istruzioni sul sito https://spid.gov.it ).
Quando riceverete l'avviso Agid che il deploy è stato effettuato, potete entrare in configurazione di
microspid, spuntare la checkbox "Attiva autenticazione SPID", salvare e, dopo aver caricato la pagina
di accesso utente, testare il funzionamento del tutto.

CONFIGURAZIONE MULTI-SITO
-------------------------
Per questa configurazione prendiamo le mosse dal file metadata.tpl.esempio.xml e immaginiamo di avere
solo due siti da raccordare nello stesso file di metadata. Il primo sito andrà configurato come quanto
indicato al paragrafo precedente, tuttavia per i metadati occorrerà servirsi del file:
metadata.tpl.esempio.xml
che andrà spostato, rinominandolo, in public://microspid/metadata.xml (dopo aver cancellato il file
esistente) per poi procedere come segue:
1) una volta installato microspid sul secondo sito si consiglia di copiare i file presenti nella cartella 
cert (in private://microspid/cert) del primo sito nella cartella corrispondente del secondo sito.
2) fatto questo configurate microspid sul secondo sito con l'avvertenza di -a) indicare 1 come indice
del servizio (e non 0) -b) indicare come "Entity ID del Service Provider" lo stesso Entity ID del 
primo sito; quindi salvate.
3) Intervenite a mano e con molta attenzione sul template metadata del primo sito (quello preso dal file di
esempio e che si trova in public://microspid/). Proseguite quindi inserendo i valori appropriati relativi 
al secondo sito nei secondi elementi con tagname:
- md:SingleLogoutService       (modificare attributo Location, in accordo con il path del secondo sito
                                per esempio: https://miosito.gov.it/sottosito/microspid_logout)
- md:AssertionConsumerService  (modificare attributo Location, in accordo con il path del secondo sito)
                                per esempio: https://miosito.gov.it/sottosito/microspid_acs)
- md:AttributeConsumingService (modificare solo se necessario)
Se avete difficoltà o dubbi è possibile servirsi del forum di http://scuolacooperativa.net/drupal7
oppure inviare una email all'autore del modulo.
4) allo stesso modo create riferimenti ad eventuali successivi siti, utilizzando copia e incolla, 
modificando i dati ed incrementando di uno gli indici.
5) comunicate ad Agid i nuovi metadata facendo riferimento all'indirizzo del primo sito
(https://<miosito.it>/microspid_metadata) contenente i riferimenti al primo e agli eventuali
successivi siti.

AUTORE E RINGRAZIAMENTI
Il modulo è stato creato da Paolo Bozzo (pagolo DOT bozzo AT gmail DOT com)
Si ringraziano:
- Maurizio Cavalletti
- Nadia Caprotti
- Antonio Todaro
- Umberto Rosini
- Helios Ciancio
- Massimo Berni
- gli sviluppatori del modulo drupal simplesamlphp_auth.
- gli sviluppatori del framework simplesamlphp
- gli sviluppatori della piattaforma OneLogin
- Rob Richards
