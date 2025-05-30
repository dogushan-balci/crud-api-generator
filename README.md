# CRUD API Generator

MySQL/MariaDB veritabanları için otomatik CRUD API oluşturucu.

## Özellikler

- MySQL/MariaDB veritabanı desteği
- Otomatik tablo keşfi
- RESTful API endpoint'leri
- CRUD operasyonları
- Güvenlik ve kimlik doğrulama
- Hata yönetimi
- API dokümantasyonu
- Docker desteği

## Gereksinimler

- PHP 8.1 veya üzeri
- MySQL 8.0 veya üzeri
- Composer
- Docker ve Docker Compose (opsiyonel)

## Kurulum

### Composer ile Kurulum

```bash
composer require crud-api-generator/crud-api-generator
```

### Docker ile Kurulum

```bash
git clone https://github.com/crud-api-generator/crud-api-generator.git
cd crud-api-generator
cp .env.example .env
docker-compose up -d
```

## Kullanım

```php
use CRUDAPIGenerator\Core\APIGenerator;

$config = [
    'host' => 'localhost',
    'dbname' => 'your_database',
    'username' => 'root',
    'password' => 'secret'
];

$api = new APIGenerator($config);
$api->generate();
```

## API Endpoint'leri

- `GET /api/{table}` - Tüm kayıtları listele
- `GET /api/{table}/{id}` - Tek bir kaydı getir
- `POST /api/{table}` - Yeni kayıt oluştur
- `PUT /api/{table}/{id}` - Kayıt güncelle
- `DELETE /api/{table}/{id}` - Kayıt sil

## Güvenlik

- API Key kimlik doğrulaması
- CORS koruması
- Rate limiting
- SQL injection koruması
- XSS koruması

## Test

```bash
composer test
```

## Kod Kalitesi

```bash
composer check
```

## Lisans

MIT

## Katkıda Bulunma

1. Fork yapın
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Değişikliklerinizi commit edin (`git commit -m 'feat: add amazing feature'`)
4. Branch'inizi push edin (`git push origin feature/amazing-feature`)
5. Pull Request oluşturun

## İletişim

Doğuşhan Balcı - [@dogushanbalci](https://twitter.com/dogushanbalci) - dogushanbalci@gmail.com

Proje Linki: [https://github.com/dogushanbalci/crud-api-generator](https://github.com/dogushanbalci/crud-api-generator) 