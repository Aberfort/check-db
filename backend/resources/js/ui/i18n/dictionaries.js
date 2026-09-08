/**
 * UI chrome only. Check titles and finding messages are translated by the API,
 * so adding a check never requires touching this file.
 */
export const dictionaries = {
    en: {
        'app.name': 'check-db',
        'app.tagline': 'SQLite data quality audit',
        'app.intro':
            'Upload a SQLite file and it is checked against its own schema — foreign keys, declared types, primary keys and indexes — then reported back with every issue found.',

        'nav.language': 'Language',
        'nav.theme': 'Theme',
        'nav.theme.light': 'Switch to light theme',
        'nav.theme.dark': 'Switch to dark theme',

        'upload.title': 'Analyse a database',
        'upload.choose': 'Choose file',
        'upload.none': 'No file selected',
        'upload.hint': 'Accepts .db, .sqlite, .sqlite3, .sql and .zip',
        'upload.profile': 'Depth',
        'upload.submit': 'Run audit',
        'upload.submitting': 'Uploading…',
        'upload.sample': 'Or run it on a sample database',
        'upload.sampleHint': 'A small shop database seeded with realistic problems.',

        'profile.quick': 'Quick',
        'profile.standard': 'Standard',
        'profile.thorough': 'Thorough',
        'profile.crawl': 'Crawl export',

        'catalogue.title': 'What gets checked',

        'status.queued': 'Queued',
        'status.processing': 'Analysing',
        'status.success': 'Done',
        'status.error': 'Failed',

        'running.title': 'Reading the database',
        'running.hint': 'Larger files take longer — results appear as soon as the run finishes.',

        'score.title': 'Health score',
        'score.caption': 'Weighted share of checks that came back clean',
        'score.checks': ':passed of :run checks passed',
        'grade.good': 'Good',
        'grade.fair': 'Fair',
        'grade.poor': 'Poor',

        'severity.critical': 'Critical',
        'severity.warning': 'Warning',
        'severity.info': 'Notice',
        'severity.all': 'All severities',

        'checks.title': 'Checks',
        'checks.passed': 'Passed',
        'checks.failed': 'Failed',
        'checks.skipped': 'Not applicable',
        'checks.skippedHint': 'Nothing in this database to check',
        'checks.findings': ':count findings',
        'checks.finding': '1 finding',
        'checks.none': 'No issues found',
        'checks.truncated': 'Showing the first :limit',

        'schema.title': 'Tables',
        'schema.rows': 'Rows',
        'schema.table': 'Table',
        'schema.tables': ':count tables · :rows rows',
        'schema.viewChart': 'Chart',
        'schema.viewTable': 'Table',

        'findings.title': 'Findings',
        'findings.search': 'Search table or column',
        'findings.export': 'Export CSV',
        'findings.empty': 'Nothing matches these filters.',
        'findings.close': 'Close',
        'findings.prev': 'Previous',
        'findings.next': 'Next',
        'findings.page': 'Page :page of :last',
        'findings.location': 'Location',

        'error.title': 'The analysis failed',
        'error.retry': 'Start over',
        'error.generic': 'Something went wrong.',

        'action.reset': 'New analysis',
        'action.copyLink': 'Copy link',
        'action.copied': 'Link copied',
    },

    uk: {
        'app.name': 'check-db',
        'app.tagline': 'Аудит якості даних SQLite',
        'app.intro':
            'Завантажте файл SQLite — його перевірять за його ж власною схемою: зовнішні ключі, оголошені типи, первинні ключі та індекси — і покажуть усі знайдені проблеми.',

        'nav.language': 'Мова',
        'nav.theme': 'Тема',
        'nav.theme.light': 'Перемкнути на світлу тему',
        'nav.theme.dark': 'Перемкнути на темну тему',

        'upload.title': 'Проаналізувати базу',
        'upload.choose': 'Обрати файл',
        'upload.none': 'Файл не обрано',
        'upload.hint': 'Приймає .db, .sqlite, .sqlite3, .sql і .zip',
        'upload.profile': 'Глибина',
        'upload.submit': 'Запустити аудит',
        'upload.submitting': 'Завантаження…',
        'upload.sample': 'Або спробувати на демо-базі',
        'upload.sampleHint': 'Невелика база магазину з навмисно закладеними проблемами.',

        'profile.quick': 'Швидко',
        'profile.standard': 'Стандартно',
        'profile.thorough': 'Ретельно',
        'profile.crawl': 'Експорт краулера',

        'catalogue.title': 'Що перевіряється',

        'status.queued': 'У черзі',
        'status.processing': 'Аналіз',
        'status.success': 'Готово',
        'status.error': 'Помилка',

        'running.title': 'Читаємо базу',
        'running.hint': 'Більші файли потребують більше часу — результат зʼявиться одразу після завершення.',

        'score.title': 'Оцінка якості',
        'score.caption': 'Зважена частка перевірок, які пройшли чисто',
        'score.checks': 'Пройдено :passed з :run перевірок',
        'grade.good': 'Добре',
        'grade.fair': 'Задовільно',
        'grade.poor': 'Погано',

        'severity.critical': 'Критично',
        'severity.warning': 'Попередження',
        'severity.info': 'Зауваження',
        'severity.all': 'Усі рівні',

        'checks.title': 'Перевірки',
        'checks.passed': 'Пройдено',
        'checks.failed': 'Не пройдено',
        'checks.skipped': 'Не застосовно',
        'checks.skippedHint': 'У цій базі немає що перевіряти',
        'checks.findings': 'Знахідок: :count',
        'checks.finding': '1 знахідка',
        'checks.none': 'Проблем не знайдено',
        'checks.truncated': 'Показано перші :limit',

        'schema.title': 'Таблиці',
        'schema.rows': 'Рядків',
        'schema.table': 'Таблиця',
        'schema.tables': 'Таблиць: :count · рядків: :rows',
        'schema.viewChart': 'Графік',
        'schema.viewTable': 'Таблиця',

        'findings.title': 'Знахідки',
        'findings.search': 'Пошук за таблицею чи колонкою',
        'findings.export': 'Експорт CSV',
        'findings.empty': 'За цими фільтрами нічого немає.',
        'findings.close': 'Закрити',
        'findings.prev': 'Назад',
        'findings.next': 'Далі',
        'findings.page': 'Сторінка :page з :last',
        'findings.location': 'Розташування',

        'error.title': 'Аналіз не вдався',
        'error.retry': 'Почати спочатку',
        'error.generic': 'Щось пішло не так.',

        'action.reset': 'Новий аналіз',
        'action.copyLink': 'Скопіювати лінк',
        'action.copied': 'Лінк скопійовано',
    },
}

export const locales = Object.keys(dictionaries)
