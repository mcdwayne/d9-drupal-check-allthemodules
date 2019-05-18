# 1. Установка:
## 1.1 Добавляем 2 модуля и включаем их.
 * `composer require 'drupal/cmlexchange:1.x-dev'`
 * `composer require 'drupal/cmlmigrations:1.x-dev'`
 * `drush en cmlexchange cmlmigrations -y`

# 2. Миграции:

## 2.1 Доступные миграции
 * `drush mim cml_taxonomy_catalog` - Каталог
 * `drush mim cml_product_variation` - Вариации
 * `drush mim cml_product` - Товары
 * `drush mim --group=cml --update` - Всё
 * Удалить всё: 
   * `drush mr cml_product && drush mr cml_product_variation && drush mr cml_taxonomy_catalog`
   * лучше в таком порядке, иначе для удаления товаров придётся комментировать `$variation_storage->delete($variations)` в `Drupal\commerce_product\Entity\Product`.

# 3. Замеры производительности
 * Исходные данные:
   * ~ 500 таксономния
   * ~ 9.5k вариаций
   * ~ 11k товаров
   * нет картинок
 * 12 мин - Контрольная загрузка без пересохранения товара 
 * 14 мин - Загрузка всех товаров 
 * 16 мин - Обновление всех товаров

# 4. Удаление модуля

Заходим на страницу `/cmlmigrations/status`  
Нажимаем на кнопку `Clear 1000 product_uuid field`  
![https://i.imgur.com/XYSoPIY.png]()    
После этого удаляем модуль как обычно  

