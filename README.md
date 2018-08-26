# WP REST LOIGIN CHECKER

## Usage
WordpressをHeadless CMSとして利用する場合に下書き状態の投稿を見る手段として利用することができます。<br>
以下のエンドポイントをコールすることでユーザー情報とWP-REST-APIに必要なnonceを得ることができます。<br>
このリポジトリのディレクトリをWordpressの`plugins`ディレクトリに配置して、Wordpressのダッシュボード > プラグインより有効化してください。

### Endpoint
```
GET your-site-domain/wp-json/v1/login-check?user={Cookie Value}
```

## Notes
`rest-login-cheker.php`の`ENCRYPT_KEY`定数は適宜変更してください。
