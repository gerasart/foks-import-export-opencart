# FOKS Import/Export from xml for Opencart 2.x-3.x
***
![This is image title](http://res2.weblium.site/res/5b45bd7f6994e20025bdd7cc/5b47697c0240710022fdab69_optimized_443 "This is image title")
***
# About Services
Вы можете легко импортировать, експортировать товары в нашу систему.

### Одна платформа, безграничные возможности!

Неважно, начинаете ли Вы свое путешествие по электронной коммерции в качестве начинающего продавца или являетесь признанным брендом.
В настоящее время FOKS объединяет более 450 различных магазинов, которые успешно продают свои товары на ROZETKA, и других марктеплейсах в том числе в Европе и США. 
Продавайте свои товары не только в Украине, но и в другие страны. Производите интеграцию вашего интернет магазина с FOKS.


## Установка 

- прямой доступ к модулю /admin/index.php?route=tool/foks&token=[токен вашей сессии]
- установить модификатор foks.ocmod.zip
- если не получилось распаковать архив и установить отдельно install.ocmod.xml через админку а файлы с папки uploads загрузить через ftp
- при скачивании с github папку uploads and index.xml добавить в архив и назвать FOKS.ocmod.zip
- если при установке выдает ошибку с папки helpers установить localcopy.ocmod.zip для Вашей версии 

## Модуль не появился в админке по адресу 

- опенкарт 2.0 - Инструменты/Foks
можно вручную добавить в 

admin/controller/common/menu.php

```$data['foks'] = $this->url->link('tool/foks', 'token=' . $this->session->data['token'], 'SSL');```

и
```
admin/view/template/common/menu.tpl
```

перед
```
<li><a href="<?php echo $upload; ?>"><?php echo $text_upload; ?></a></li>
```

вставить
```<li><a href="<?php echo $foks; ?>">Foks</a></li>```


## Импорт с изображениями

- частая проблема падение по крону с 504 ошибкой по таймаут, нужно на сервере увеличить время таймаута.

```
    PHP: max_execution_time = 30000
    NGINX: fastcgi_read_timeout 30000;
    proxy_connect_timeout 30000s;
    proxy_read_timeout 30000s;
    proxy_send_timeout 30000s;
```

## Жалемые характеристики хостинга

```
2x Xeon 64bit
4 GB RAM
```
