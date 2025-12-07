# Cold-Hot Game (Холодно-Горячо)

[![Packagist Version](https://img.shields.io/packagist/v/ganchenkov/cold-hot)](https://packagist.org/packages/ganchenkov/cold-hot)
[![PHP Version](https://img.shields.io/packagist/php-v/ganchenkov/cold-hot)](https://packagist.org/packages/ganchenkov/cold-hot)
[![License](https://img.shields.io/packagist/l/ganchenkov/cold-hot)](https://packagist.org/packages/ganchenkov/cold-hot)

Консольная игра "Холодно-горячо" — угадай трехзначное число без повторяющихся цифр.

**Packagist:** https://packagist.org/packages/ganchenkov/cold-hot

## Описание игры

Компьютер загадывает трехзначное число, в котором все цифры уникальны (не повторяются). Задача игрока — угадать это число за минимальное количество попыток.

### Правила

1. Секретное число состоит из трёх цифр (от 0 до 9), при этом первая цифра не может быть нулём
2. Все цифры в числе уникальны (не повторяются)
3. После каждой попытки игрок получает три подсказки (по одной на каждую цифру):
   - **Горячо** — цифра угадана и находится на своём месте
   - **Тепло** — цифра есть в загаданном числе, но на другом месте
   - **Холодно** — такой цифры нет в загаданном числе
4. Подсказки выводятся в алфавитном порядке (чтобы не раскрывать позиции)
5. Игра продолжается до тех пор, пока число не будет угадано

## Требования к окружению

- PHP 8.0 или выше
- Composer
- Расширение PDO SQLite (обычно включено по умолчанию)

## Установка

### Глобальная установка (рекомендуется)

```bash
composer global require ganchenkov/cold-hot
```

После установки добавьте путь к глобальным бинарникам Composer в переменную PATH:
- **Linux/macOS:** `~/.composer/vendor/bin`
- **Windows:** `%APPDATA%\Composer\vendor\bin`

Теперь можно запускать игру просто командой `cold-hot`.

### Локальная установка

```bash
composer require ganchenkov/cold-hot
```

### Установка из исходников

1. Клонируйте репозиторий:
   ```bash
   git clone https://github.com/ganchenkov/cold-hot.git
   cd cold-hot
   ```

2. Установите зависимости:
   ```bash
   composer install
   ```

## Запуск

### При глобальной установке

```bash
cold-hot --new      # Новая игра
cold-hot --list     # Список партий
cold-hot --replay 1 # Воспроизвести партию #1
cold-hot --help     # Справка
```

### При локальной установке

```bash
./vendor/bin/cold-hot --new
# или
php vendor/bin/cold-hot --new
```

### Из исходников

```bash
php bin/cold-hot --new
```

### Команды

| Команда | Сокращение | Описание |
|---------|------------|----------|
| `--new` | `-n` | Начать новую игру |
| `--list` | `-l` | Показать список партий |
| `--replay <ID>` | `-r <ID>` | Воспроизвести партию |
| `--help` | `-h` | Справка |

## Структура проекта

```
cold-hot/
├── bin/
│   └── cold-hot           # Исполняемый скрипт
├── src/
│   ├── Controller.php     # Контроллер игры
│   ├── View.php           # Модуль отображения
│   ├── Model.php          # Игровая логика
│   └── Database.php       # Работа с БД (SQLite)
├── tests/                 # Тесты
├── composer.json          # Конфигурация Composer
└── README.md              # Документация
```

## Лицензия

MIT
