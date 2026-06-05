# Canva Connector for Piwigo

Canva Connector lets the Canva **Piwigo Media** app connect to a Piwigo
instance without sending Piwigo API keys or passwords to a central backend.

## Install

Upload this folder to:

```text
<piwigo-root>/plugins/canva_connector
```

Then open:

```text
https://your-piwigo.example.com/plugins/canva_connector/connect.php
```

while logged in as a Piwigo administrator.

## Connect Canva

1. Review the access warning.
2. Click **Authorize and generate token**.
3. Copy the generated token.
4. Paste it into the Canva Piwigo Media app.

## Permissions

The generated connector token allows Canva Piwigo Media to:

- list albums
- read photos selected for insertion into Canva
- upload Canva exports to a selected album

The token does not expose your Piwigo password or Piwigo API keys.

## Revoke access

Open `connect.php` again and click **Revoke** for the token.
