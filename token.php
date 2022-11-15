<?php
$token_params = http_build_query(array(
    "client_id" => "Collab-PHP",
    "redirect_uri" => "https://collab-php-collab-example.apps-dev.hbp.eu/token.php",
    "grant_type" => "authorization_code",
    "code" => filter_input(INPUT_GET, "code"),
    "client_secret" => getenv("client_secret")
        ));
$token_ch = curl_init("https://iam.ebrains.eu/auth/realms/hbp/protocol/openid-connect/token");
curl_setopt_array($token_ch, array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $token_params
));
$token_res = curl_exec($token_ch);
curl_close($token_ch);

$token_obj = json_decode($token_res, true);
$token = $token_obj["access_token"];

$state = json_decode(urldecode(filter_input(INPUT_GET, "state")), true);
$state["token"] = $token;
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Collab example</title>
        <meta charset="UTF-8">
        <script>
            const state = <?php echo json_encode($state); ?>;
            const bucket = state["clb-collab-id"];
            const token = state.token;

            async function save() {
                const upload = await fetch(
                        `https://data-proxy.ebrains.eu/api/v1/buckets/${bucket}/hello.txt`, {
                            method: "PUT",
                            headers: {
                                authorization: `Bearer ${token}`
                            }
                        }
                ).then(response => response.json());
                if (!upload.hasOwnProperty("url")) {
                    alert("Can't save: " + JSON.stringify(upload));
                    return;
                }
                fetch(upload.url, {
                    method: "PUT",
                    headers: {
                        'Content-Type': 'text/plain'
                    },
                    body: document.getElementById("input").value
                });
            }

            async function load() {
                const download = await fetch(
                        `https://data-proxy.ebrains.eu/api/v1/buckets/${bucket}/hello.txt?redirect=false`, {
                            headers: {
                                authorization: `Bearer ${token}`
                            }
                        }
                ).then(response => response.json());
                if (!download.hasOwnProperty("url")) {
                    alert("Can't load: " + JSON.stringify(download));
                    return;
                }
                fetch(download.url)
                        .then(response => response.text())
                        .then(text => document.getElementById("output").innerText = text);
            }
        </script>
    </head>
    <body>
        <textarea id="input" rows="5" style="width:80%"></textarea><br>
        <button onclick="save()">Save</button><hr>
        <button onclick="load()">Load</button>
        <div id="output"></div>
    </body>
</html>
