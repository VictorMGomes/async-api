<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name') }} - AsyncAPI Docs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://unpkg.com/@asyncapi/react-component@3.0.2/styles/default.min.css">

    <style>
        body { margin: 0; padding: 0; }
        #asyncapi { height: 100vh; }
    </style>
</head>
<body>

    <div id="asyncapi"></div>

    <script src="https://unpkg.com/@asyncapi/react-component@3.0.2/browser/standalone/index.js"></script>

    <script>
        const schemaUrl = "{{ route('docs.ws.json') }}";

        fetch(schemaUrl)
            .then(response => response.text())
            .then(schema => {
                AsyncApiStandalone.render({
                    schema: schema,
                    config: {
                        show: {
                            sidebar: true,
                            info: true,
                            operations: true,
                            messages: true,
                            servers: true,
                            errors: true,
                        }
                    }
                }, document.getElementById('asyncapi'));
            })
            .catch(err => {
                console.error("Error loading schema:", err);
                document.getElementById('asyncapi').innerHTML = '<p style="color:red; padding:20px;">Error loading the JSON documentation.</p>';
            });
    </script>
</body>
</html>
