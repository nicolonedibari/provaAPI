<?php
$GROQ_API_KEY = getenv('GROQ_API_KEY');

// Endpoint corretto di Groq
$url = "https://api.groq.com/openai/v1/chat/completions";

// Verifica se è stato inviato il form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_input = $_POST['user_input']; // Ottieni il testo inviato dall'utente

    // Inizializza cURL
    $ch = curl_init($url);

    // Dati della richiesta
    $request_array = array(
        "model" => "llama-3.3-70b-versatile",
        "messages" => array(
            array(
                "role" => "system",
                "content" => "Sei un assistente utile e conciso che risponde in italiano."
            ),
            array(
                "role" => "user",
                "content" => $user_input
            )
        )
    );

    // Converte in JSON
    $json_string = json_encode($request_array);

    // Impostazioni cURL
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_string);

    // Header HTTP
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $GROQ_API_KEY"
    ]);

    // Esegui richiesta
    $risp = curl_exec($ch);

    // Status code
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code != 200) {
        echo "Errore nella richiesta API. Codice di stato: $http_code.<br>";
        echo "Risposta API: " . htmlspecialchars($risp);
        curl_close($ch);
        exit;
    }

    // Chiudi cURL
    curl_close($ch);

    // Decodifica risposta
    $response_array = json_decode($risp, true);

    // Verifica se la risposta contiene il campo "choices"
    if (isset($response_array['choices'][0]['message']['content'])) {
        $response_content = $response_array["choices"][0]["message"]["content"];
    } else {
        $response_content = "Errore: la risposta dell'API non è valida.";
    }

    // Verifica se ci sono anche token di reasoning nella risposta
    $reasoning = isset($response_array["choices"][0]["message"]["reasoning"]) ? $response_array["choices"][0]["message"]["reasoning"] : null;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interazione con Groq</title>
</head>
<body>
    <h2>Domanda a Groq</h2>
    <form method="POST">
        <label for="user_input">Inserisci la tua domanda:</label>
        <input type="text" id="user_input" name="user_input" required>
        <button type="submit">Invia</button>
    </form>

    <?php if ($_SERVER["REQUEST_METHOD"] == "POST"){?>
        <h3>Domanda dell'utente:</h3>
        <p><?php echo htmlspecialchars($user_input); ?></p>

        <h3>Risposta del modello:</h3>
        <p><?php echo htmlspecialchars($response_content); ?></p>

        <?php if ($reasoning){ ?>
            <h3>Reasoning (se disponibile):</h3>
            <p><?php echo htmlspecialchars($reasoning); ?></p>
        <?php } ?>
    <?php } ?>
</body>
</html>