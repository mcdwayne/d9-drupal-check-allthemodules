# CML API
Настраивается на странице /admin/structure/cml/settings, как настраивать:
 * type(string|[]|keyval|attr) - строка, ключ-значение,массив, атрибут. По умолчанию строка.
 * dst(''|offers) - не групировать значения по протукт-uuid
 * skip(1) - пропустить значение
 * attr('АТРИБУТ') - навание атрибута
## Конфиг по умолчанию:
```yml
Ид:
Артикул:
Штрихкод:
Наименование:
ПометкаУдаления:
БазоваяЕдиница: {type: 'attr', skip: 1, attr: 'НаименованиеПолное'}
Группы: {type: []}
Категория:
Описание:
Картинка:
Изготовитель: {type: 'keyval'}
СтавкиНалогов: {type: 'keyval', skip: 1}
ЗначенияСвойств: {type: 'keyval'}
ЗначенияРеквизитов: {type: 'keyval'}
```
Иногда для неумелых настройщиков 1С стоит сделать так:
```yml
ХарактеристикиТовара: {type: [], dst: 'offers'}
Штрихкод: {dst: 'offers'}
```

## Результат стандартного маппинга
```yml
product:
  Id: 155a9b88-def1-11e5-499f-0050569c5328
  Artikul: БЛ-7
  Strihkod: 2100001000004
  Naimenovanie: бумага
  PometkaUdalenia: ''
  Gruppy: ["548f45ce-fdbd-11e6-8064-0cc47ace1218"]
  Kategoria: '02a378a2-def1-11e5-499f-0050569c5328'
  Opisanie: 'Теплоизоляционные плиты марки Пеноплэкс Комфорт® активно применяют для...'
  Kartinka: import_files/54/548f45d0fdbd11e680640cc47ace1218_a2cbe69fbe2b11e7b8450cc47ace1218.jpeg
  Izgotovitel:
    89633896-0671-11e8-8cb4-0cc47ace1218: Иваново
  ZnaceniaSvoistv:
    54a7f560-0663-11e8-8cb4-0cc47ace1218: "1\_200"
    bd424605-0661-11e8-8cb4-0cc47ace1218: '600'
    824f0591-068e-11e8-8cb4-0cc47ace1218: '50'
    374dcbb9-0663-11e8-8cb4-0cc47ace1218: 374dcbba-0663-11e8-8cb4-0cc47ace1218
  ZnaceniaRekvizitov:
    ВидНоменклатуры: 'ПАЗОГРЕБНЕВЫЕ ПЛИТЫ'
    ТипНоменклатуры: Товар
    'Полное наименование': 'бумага, на которой можнопечатать'
offers:
  548f45d0-fdbd-11e6-8064-0cc47ace1218:
    HarakteristikiTovara: null
skip:
  BazovaaEdinica: Штука
  StavkiNalogov:
    НДС: '18'
```
