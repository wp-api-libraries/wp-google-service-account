# WP Google Service Account
GCP service accounts the WordPress way

### How to use

The `WPGoogleServiceAccount` class constructor takes 2 arguments. The first is the JSON service account key provided by google decoded into an array. The second is the scope to which you would like to authenticate the service account to. 
   - Find more scopes at the [Google Oauth 2.0 Playground](https://developers.google.com/oauthplayground/).
   
Calling the `get_token` method will retrieve a brand new token using google's Oauth 2.0 API endpoints, or it'll return a previously cached token if available.

```php
<?php
$service_key = json_decode('{
  "type": "service_account",
  "project_id": "your-gcp-project-id",
  "private_key_id": "11111122222233333333444444455555",
  "private_key": "-----BEGIN PRIVATE KEY-----\nsdfsdfasga@EF@EVSDVWEXVVSDVSDVSwERWRWDVDFBFBBTBTSDVSDVSVDDVDVDSVSDVSFBDFBDFBDFVDSVSDVSDVSDVBFBETKILOL^%%%%%%FGTNTNTHN=\n-----END PRIVATE KEY-----\n",
  "client_email": "user-name@your-gcp-project-id.iam.gserviceaccount.com",
  "client_id": "12312423423423524523",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "https://www.googleapis.com/robot/v1/metadata/x509/user-name%40your-gcp-project-id.iam.gserviceaccount.com"
}
', true);

// Takes 2 arguments, the service key as an array and the scope to authenticate.
// You can find differenct API scopes here <https://developers.google.com/oauthplayground/>
$gcp_service_account = new WPGoogleServiceAccount( $service_key, 'https://www.googleapis.com/auth/cloud-platform');

// Takes care of retrieving, storing, and refreshing the token as needed.
$token  = $gcp_service_account->get_token();
```