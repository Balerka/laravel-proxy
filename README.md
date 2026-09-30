# Laravel Proxy

Пакет для Laravel 12/13 и Guzzle 7: один прокси или последовательное переключение между несколькими прокси при ошибках соединения.

## Установка

Установка напрямую из GitHub:

```bash
composer config repositories.laravel-proxy vcs https://github.com/Balerka/laravel-proxy
composer require balerka/laravel-proxy:dev-main
```

Laravel автоматически зарегистрирует провайдер пакета.

## Один прокси в `.env`

Укажи адрес и порт в переменной `HTTP_PROXY`:

```dotenv
HTTP_PROXY="http://proxy.example.com:8080"
```

Если протокол не указан, пакет добавит `http://`:

```dotenv
HTTP_PROXY="proxy.example.com:8080"
```

Для прокси с логином и паролем:

```dotenv
HTTP_PROXY="http://username:password@proxy.example.com:8080"
```

Специальные символы в логине и пароле нужно URL-кодировать. Например, пароль `p@ss:word` записывается как `p%40ss%3Aword`:

```dotenv
HTTP_PROXY="http://username:p%40ss%3Aword@proxy.example.com:8080"
```

## Несколько прокси в `.env`

В той же переменной `HTTP_PROXY` укажи JSON-массив **в одну строку**. Внешние кавычки — одинарные, кавычки внутри JSON — двойные:

```dotenv
HTTP_PROXY='["http://proxy1.example.com:8080","http://proxy2.example.com:8080","http://proxy3.example.com:8080"]'
```

Каждый прокси может иметь свои логин и пароль:

```dotenv
HTTP_PROXY='["http://user1:password1@proxy1.example.com:8080","http://user2:password2@proxy2.example.com:8080"]'
```

Адреса без протокола также допустимы:

```dotenv
HTTP_PROXY='["proxy1.example.com:8080","proxy2.example.com:8080"]'
```

Не используй просто адреса через запятую: для нескольких прокси нужен корректный JSON-массив. Пустые элементы удаляются, одинаковые адреса после нормализации объединяются с сохранением порядка.

### Как выбирается прокси

- Каждый новый запрос начинается с первого прокси в списке.
- При ошибке соединения, ответе `407` или ошибке CONNECT-туннеля пакет пробует следующий прокси.
- Каждый адрес используется не более одного раза за проход списка. После последнего прокси ошибка или ответ возвращается вызывающему коду; перехода на прямое соединение нет.
- Обычные ответы сервера, например `500`, не вызывают переключение.
- При cURL-таймауте после установления соединения запросы, кроме `GET`, `HEAD` и `OPTIONS`, не повторяются. Это снижает риск повторной отправки POST-запроса.

Это резервирование по порядку: пакет не выбирает случайный прокси и не запоминает последний успешный адрес между запросами.

## Отключение прокси

Оставь значение пустым или укажи пустой массив:

```dotenv
HTTP_PROXY=""
```

```dotenv
HTTP_PROXY='[]'
```

Запросы, использующие настройки пакета, будут идти напрямую.

## Подключение к запросам

Пакет читает `HTTP_PROXY` для HTTP- и HTTPS-запросов. Отдельную переменную `HTTPS_PROXY` пакет не использует. Логика переключения включается только там, где подключены `ProxyOptions`.

### Guzzle

```php
use Balerka\LaravelProxy\ProxyOptions;
use GuzzleHttp\Client;

$client = new Client([
    ...ProxyOptions::make(),
    'timeout' => 30,
]);

$response = $client->get('https://example.com');
```

`make()` создаёт Guzzle handler stack с переключением прокси и задаёт таймаут подключения в 5 секунд по умолчанию. Общий таймаут запроса задаётся отдельно.

### Laravel HTTP Client

```php
use Balerka\LaravelProxy\ProxyOptions;
use Illuminate\Support\Facades\Http;

$response = Http::withMiddleware(ProxyOptions::middleware())
    ->connectTimeout(config('proxy.connect_timeout', 5))
    ->timeout(30)
    ->get('https://example.com');
```

`middleware()` добавляет переключение прокси в существующий HTTP-клиент; таймауты нужно задавать отдельно.

## Применение изменений `.env`

Если конфигурация закеширована, после изменения `.env` пересобери кеш:

```bash
php artisan config:cache
```

При локальной разработке можно очистить кеш:

```bash
php artisan config:clear
```

Если запросы выполняются долгоживущими процессами, перезапусти их. Например, для обработчиков очередей:

```bash
php artisan queue:restart
```

## Публикация конфигурации

Необязательно: чтобы изменить настройки пакета, опубликуй `config/proxy.php`:

```bash
php artisan vendor:publish --tag=proxy-config
```

В файле доступны `list` — список прокси из `HTTP_PROXY` — и `connect_timeout` — таймаут подключения для `make()` в секундах.

Все адреса и учётные данные в примерах вымышлены. Не добавляй `.env` с реальными паролями в Git.
