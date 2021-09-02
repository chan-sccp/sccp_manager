## Добро пожаловать странник на SCCP страницу веб интерфейса для FreePBX (SCCP Manager)
| [English :gb:/:us:](README.md) | [Russian :ru:](README.ru.md) | [Старая страница проекта](https://github.com/PhantomVl/sccp_manager/tree/master)

![Gif](https://github.com/chan-sccp/sccp_manager/raw/develop/.dok/image/Demo_1s5.gif)

  * [Installation](https://github.com/chan-sccp/sccp_manager#installation)
  * [Prerequisites](https://github.com/chan-sccp/sccp_manager#prerequisites)
  * [Links](https://github.com/chan-sccp/sccp_manager#link)
  * [Wiki](https://github.com/chan-sccp/sccp_manager/wiki)

## Link

[![Download Sccp-Mamager](https://img.shields.io/badge/SccpGUI-build-ff69b4.svg)](https://github.com/chan-sccp/sccp_manager/archive/tarball/develop)
[![Download Chan-SCCP channel driver for Asterisk](https://img.shields.io/sourceforge/dt/chan-sccp-b.svg)](https://github.com/chan-sccp/chan-sccp/releases/latest)
[![Chan-SCCP Documentation](https://img.shields.io/badge/docs-wiki-blue.svg)](https://github.com/chan-sccp/chan-sccp/wiki)

### История
.... давнем давно в далеком прошлом ....
Группа программистов пыталось быстро бороться с несовершенством продуктов CISCO, но повседневные дела угробили проект.
Но на помощь им пришел молодой программист и возродил идею уже заброшенного проекта.
Для желающих попробовать себя в этой борьбе на просторах программирования ссылка на проект (https://github.com/Cynjut/SCCP_Manager).

### Кому это надо...
Ну в первую очередь для Себя любимого ну и для тех, у кого есть куча телефонного хлама от компании Cisco. 
Если вы планируете использовать Aserisk + FreePBX, то я надеюсь, что данный модуль существенно упростит управление и настройки телефонами от Cisco.
В интернете существует замечательный проект (IMHO), который интегрирует проприетарный протокол Cisco в Asterisk, конечно, он пока далек от идеала, 
но все же это замечательная замена серверам CCME, СCM, СUСM !
Ну я совершенно не представляю себе, сколько времени данный проект будет поддерживаться.

### Ну если ты еще с нами ...

Как я говорил выше, это дополнение к (Aserisk + FreePBX), но нам еще потребуется:
 1. У меня не получилось поставить добиться работы с дисками Aserisk и FreePBX - собираем из исходников 
 1.1. Замечательная копания freepbx. Теперь с SNG7-PBX-64bit-... все работает!
 2. Mysql (Maria)
 3. Драйвер протокола SCCP страница (https://github.com/chan-sccp/chan-sccp/)
 4. Этот модуль.
 5. Руки
 6. Возможно еще несколько проектов

### Вжно! В этой ветке лежат самые последне нововведения и обновления, и самые последние БАГИ! 
    Пользуйся и наслождайся. Так же не забывай писать нам об ошибках, которые ты нашел! 
    Это очень нам поможет, Я с радостью исправлю то, что ты нашел и добалю новых.

### Wiki - Основные Инструкции по настройке 
Вся документация по проекту пока лежит на старой Вики [![SCCP Manager Wiki](https://img.shields.io/badge/Wiki-new-blue.svg)](https://github.com/PhantomVl/sccp_manager/wiki)
Вся документация по драйверу Chan-SCCP  [![SCCP Manager Wiki](https://img.shields.io/badge/Wiki-new-blue.svg)](https://github.com/chan-sccp/wiki)

Ну и как водится у на SCCP Manager это бесплатное дополнение. И помни "(C)" означает "Копия верна". Please see the file COPYING for details.


### Prerequisites - как говориться все, что хуже этого возможно работать тоже будет .... но только вопрос как ?
Make sure you have the following installed on your system:
- PHPx.x-zip has to be installed (where x.x is the installed version of PHP).
  For example, on Debian, using PHP7.3

- pbx:
  - asterisk >= 1.8 (absolute minimum & not recommended)
  - asterisk >= 13.7 or asterisk >= 14.0 or asterisk >= 15.0 (Тестировалось на стендах)
- gui:
  - freepbx >= 13.0.192 (http://wiki.freepbx.org/display/FOP/Install+FreePBX)
- standard posix compatible applications like sed, awk, tr
- a working version of [chan-sccp](https://github.com/chan-sccp/chan-sccp)
- PHPx.x Ну тут уж как повезет, 5.6 от freepbx, но мы уже пишем под PHP7.3

```
apt-get install PHP7.3-zip
```
### Installation Очень короткая инструкция
- открой полную инструкцию [Полная версия инструкции] (https://github.com/PhantomVl/sccp_manager/wiki/step-by-step-instlation)

### Installation Другие инструкции по установке :-)
- [Setting up a FreePBX system](http://wiki.freepbx.org/display/FOP/Install+FreePBX)
- [Setting up Chan-Sccp](https://github.com/chan-sccp/chan-sccp/wiki/How-to-setup-the-chan_sccp-Module)
- [See chan-sccp wiki](https://github.com/chan-sccp/chan-sccp/wiki/Realtime-Configuration).


## Установка в Веб морде 

-----

1. Log in to FreePBX
2. Go to Admin -> Module Admin
3. Click Upload Modules.
4. Enter one of the following urls:

Мы решили, что это стабильная версия:

```
https://github.com/chan-sccp/sccp_manager/archive/refs/heads/Legacy.zip
```

Для тех, кто ищет нового и интересного:

_This is development software and so may have issues_
```
https://github.com/chan-sccp/sccp_manager/archive/refs/heads/develop.zip
```

5. Жми Download From Web.
6. Открывай Manage Local Modules.
7. Практически в конце списка "SCCP Manager". Тут и так понятно, выбрать "Install",  и нажать "Process".
8. "Confirm installation".
9. "Close" Status window.
10. Красная кнопка "Apply" в правом верхнем углу.
11. Далее вопрос ни одного научного труда [Using-SCCP_Manager-to-Manage-chan-sccp](https://github.com/chan-sccp/chan-sccp/wiki/Using-SCCP_Manager-to-Manage-chan-sccp)

### Важно:   
   - !!! Если это это проект не заработал на твоей системе - переключись на ветку мастер [master](https://github.com/PhantomVl/sccp_manager) 
     !!! Но есть ограничение - ветка master не поддерживает изменения в chan-sccp сделаные после октября 2018 г.
   - И чуть не забыл настраиваем Realtime-Configuration ([See](https://github.com/chan-sccp/chan-sccp/wiki/Realtime-Configuration)).
   - Желательно иметь Firmware телефонов Cisco, языковые пакеты ну всякое разное.
   - Возможно, ты найдешь, то, что ищешь, в проекте (https://github.com/dkgroot/provision_sccp)
   - Если что-то не так [Wiki GUI] (https://github.com/PhantomVl/sccp_manager), [Wiki chan-sccp] (https://github.com/chan-sccp/chan-sccp/wiki),

### Chat
[![Gitter](https://badges.gitter.im/chan-sccp/chan-sccp.svg)](https://gitter.im/sccp_manager/community)

