<?php
$GROQ_API_KEY = getenv('GROQ_API_KEY');
$url = "https://api.groq.com/openai/v1/chat/completions";

$cronologia_file = "cronologia.csv";
$array = [];

// Se il file esiste, carica la cronologia
if (file_exists($cronologia_file)) {
    $fp = fopen($cronologia_file, "r");
    while (($data = fgetcsv($fp, 0, ",")) !== FALSE){
        $array[] = [
            "role" => $data[0],
            "content" => $data[1]
        ];
    }
    fclose($fp);
}

//aggiungi system (solo se chat vuota)
if (empty($array)) {
    $array[] = [
        "role" => "system",
        "content" => "Sei un assistente utile e rispondi in italiano."
    ];
}

//se è stato inviato il form
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_input = $_POST['user_input'];

    //salva input dell'utente
    $fp = fopen($cronologia_file, "a");
    fputcsv($fp, ["user", $user_input]);
    fclose($fp);

    //aggiungi al contesto
    $array[] = [
        "role" => "user",
        "content" => $user_input
    ];

    //prepara richiesta
    $request_array = [
        "model" => "llama-3.3-70b-versatile",
        "messages" => $array
    ];

    $json_string = json_encode($request_array);

    // cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_string);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $GROQ_API_KEY"
    ]);

    $risp = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($http_code != 200) {
        echo "Errore API ($http_code): " . htmlspecialchars($risp);
        curl_close($ch);
        exit;
    }

    curl_close($ch);

    $response_array = json_decode($risp, true);

    if (isset($response_array['choices'][0]['message']['content'])) {
        $response_content = $response_array['choices'][0]['message']['content'];
    } else {
        $response_content = "Errore: risposta non valida.";
    }

    // Salva risposta assistant
    $fp = fopen($cronologia_file, "a");
    fputcsv($fp, ["assistant", $response_content]);
    fclose($fp);

    //aggiungi alla memoria runtime
    $array[] = [
        "role" => "assistant",
        "content" => $response_content
    ];
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Chat con Groq</title>
</head>
<body>

<h2>Chat con Groq</h2>

<form method="POST">
    <input type="text" name="user_input" required>
    <button type="submit">Invia</button>
</form>

<hr>

<h3>Risposta del modello:</h3>

<?php if (isset($response_content)) { ?>
    <p><?php echo htmlspecialchars($response_content); ?></p>
<?php } ?>

</body>
</html>