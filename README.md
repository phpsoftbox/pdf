# PhpSoftBox Pdf

Компонент генерации PDF для PhpSoftBox.

## Назначение

- рендерить HTML в PDF через Chromium (Gotenberg);
- задавать точные размеры страницы (в т.ч. термоэтикетки);
- отделить движок рендера интерфейсом `PdfRendererInterface`;
- отдавать PDF и для просмотра в браузере (`inline`), и для скачивания (`attachment`).

## Почему Gotenberg

`Gotenberg` это HTTP-сервис, который использует Chromium для печати HTML в PDF.

Плюсы для нашего кейса:
- современный рендер CSS/HTML (включая `@page`, print-стили);
- точные размеры листа/этикетки, поля, масштаб;
- отдельный процесс/контейнер, не нагружает PHP-процесс браузерным runtime;
- хорошо масштабируется горизонтально под batch-задачи.

В пакете реализован адаптер:
- `PhpSoftBox\Pdf\Gotenberg\GotenbergHtmlPdfRenderer`
- endpoint по умолчанию: `/forms/chromium/convert/html`

## Быстрый старт

```php
use PhpSoftBox\Pdf\Gotenberg\GotenbergHtmlPdfRenderer;
use PhpSoftBox\Pdf\PdfMargins;
use PhpSoftBox\Pdf\PdfPageSize;
use PhpSoftBox\Pdf\PdfRenderOptions;
use PhpSoftBox\Pdf\PdfUnit;

$renderer = new GotenbergHtmlPdfRenderer(
    client: $client,
    requestFactory: $requestFactory,
    streamFactory: $streamFactory,
    baseUrl: 'http://gotenberg:3000',
);

$document = $renderer->renderHtml(
    html: '<html><body>Этикетка #1</body></html>',
    options: new PdfRenderOptions(
        pageSize: new PdfPageSize(58, 40, PdfUnit::Mm),
        margins: PdfMargins::all(1, PdfUnit::Mm),
        printBackground: true,
    ),
);
```

Хелпер для этикеток:

```php
$options = PdfRenderOptions::labelMm(58, 40, marginMm: 1);
```

## Возврат PDF в HTTP-ответ

Для этого есть `PdfHttpResponder`.

```php
use PhpSoftBox\Pdf\Http\PdfHttpResponder;

$responder = new PdfHttpResponder($responseFactory, $streamFactory);

// Просмотр в браузере
$response = $responder->inline($document, 'label.pdf');

// Скачивание файла
$response = $responder->download($document, 'label.pdf');
```

Компонент сам выставляет:
- `Content-Type`;
- `Content-Length`;
- `Content-Disposition` (`inline` или `attachment`);
- `Cache-Control`.

## Большие batch-задачи (1000+ страниц)

Рекомендуемая стратегия:
1. Генерировать PDF в фоне через очередь (`Queue`), не в HTTP-запросе.
2. Делить задание на чанки по страницам/этикеткам.
3. Сохранять каждый чанк как отдельный PDF в Storage.
4. По завершении формировать индекс/архив или выдавать ссылки на части.

Для разбиения по диапазонам страниц есть:
- `PdfChunkPlanner::split(totalPages, pagesPerChunk)`.

Пример:

```php
use PhpSoftBox\Pdf\Batch\PdfChunkPlanner;

$chunks = PdfChunkPlanner::split(totalPages: 1200, pagesPerChunk: 250);
// 1-250, 251-500, 501-750, 751-1000, 1001-1200
```

## Тестовые артефакты PDF

В пакете предусмотрен каталог для артефактов интеграционных тестов:
- `local/tests/pdf`

Он уже настроен через `.gitignore`, поэтому бинарные PDF не попадут в git.

Чтобы включить сохранение артефактов в интеграционных тестах:

```bash
PDF_TEST_SAVE_ARTIFACTS=1 vendor/bin/phpunit
```

## Рекомендации для термопечати

- Всегда явно задавайте `PdfPageSize` и `PdfMargins`.
- Отключайте лишние отступы в CSS и используйте print-стили.
- Для пиксельной точности этикеток тестируйте шаблон на целевом принтере.
