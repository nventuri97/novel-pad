<?php
// admin_utils.php
// Assicurati che non ci siano spazi o linee vuote prima di questo tag.

// Qui correggiamo i percorsi di include/require.
// Se 'config.php' e 'db-client.php' si trovano nella stessa cartella di 'admin_utils.php',
// possiamo usare __DIR__ per riferirci alla cartella corrente.

include_once __DIR__ . '/config.php';
require_once __DIR__ . '/db-client.php';

// Se serve includere altri file specifici per la gestione Admin,
// puoi farlo qui allo stesso modo, ad esempio:
// require_once __DIR__ . '/admin.php';

/**
 * Inserisci qui le funzioni di utilità per gli Admin.
 * Ad esempio:
 */
function myAdminUtilityFunction() {
    // ...
    return true;
}
