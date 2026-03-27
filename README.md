# Magic Forms (форк Infocity)

Форк `martin/wn-forms-plugin` для Winter CMS, подготовленный для повторного использования в ваших проектах.

## Что добавлено в этом форке

- Базовый функционал Magic Forms (формы, валидация, хранение, экспорт).
- Отображение статусов доставки писем в админке (`/backend/martin/forms/records`).
- Надежная отправка писем через outbox + очередь:
  - неотправленные письма сохраняются в outbox;
  - планировщик переотправляет `pending/failed`;
  - после восстановления почты письма уходят автоматически.

## Установка из GitHub

### Вариант A: через VCS-репозиторий (private/public, без Packagist)

1. Добавьте репозиторий в `composer.json` проекта:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/Djentelman57/wn-forms-plugin"
    }
  ]
}
```

2. Установите плагин:

```bash
composer require martin/wn-forms-plugin
php artisan winter:up
```

### Вариант B: локальный `path`-репозиторий (для разработки)

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "plugins/martin/forms"
    }
  ]
}
```

Затем:

```bash
composer require djentelman57/wn-forms-plugin:* --prefer-source
php artisan winter:up
```

## Настройка очереди писем (рекомендуется)

Для надежных ретраев используйте `database`-очередь:

```env
QUEUE_CONNECTION=database
MAIL_MAILER=smtp
```

Далее выполните:

```bash
php artisan queue:table
php artisan queue:failed-table
php artisan migrate
php artisan queue:work
```

Также убедитесь, что работает scheduler (`schedule:run` через cron), потому что этот форк каждые 5 минут заново ставит в очередь элементы outbox со статусами `pending/failed`.

## Важно перед первой публикацией

- Пакет уже настроен как `djentelman57/wn-forms-plugin`.
- Если URL вашего репозитория отличается, обновите ссылки в:
  - `composer.json` (`homepage`, `support`);
  - `Plugin.php` (`pluginDetails.homepage`).
