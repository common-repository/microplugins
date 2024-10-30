=== Microplugins ===
Contributors: andaniel05
Tags: admin, administration, code, php, plugins, wordpress
Requires at least: 4.6
Tested up to: 4.6
Stable tag: 1.1.3
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Añade funcionalidad al sitio mediante código desde la administración.

== Description ==

Útil para cualquier sitio WordPress.

Normalmente cuando se necesita añadir alguna funcionalidad al sitio se tienen 2 opciones por ese orden:

1. Instalar algún plugin que cumpla con las necesidades.
2. Añadir código al archivo 'functions.php' del tema activo.

La utilidad de los microplugins está relacionada con el punto 2 de la lista de opciones anterior.

Definimos un microplugin como el fragmento de código que se necesita añadir al archivo 'functions.php' del tema activo para conseguir la funcionalidad deseada.

Los microplugins se crean en forma de entradas WordPress y tienen código PHP válido en su contenido.

Añadirle funcionalidad al sitio mediante microplugins tiene las siguientes ventajas:
1. Sus funcionalidades son globales al sitio y no dependen del tema activo por lo que se evita tener que modificar el archivo 'functions.php' del mismo.
2. Se tiene en un único lugar y de una forma más organizada el listado de funcionalidades que se han creado.
3. Facilidad a la hora de manipularlos y ver los resultados.
4. Al ser entradas de WordPress cuentan con un control de versiones mediante las revisiones.

El principal problema que se puede presentar a la hora de trabajar con microplugins consiste en que el código introducido por el usuario puede presentar errores fatales y ocasionar que el sitio quede fuera de funcionamiento. Es importante aclarar que en este caso el microplugin sería desactivado automáticamente y el sitio estaría fuera de funcionamiento solo por un instante.

IMPORTANTE: Si en algún momento fuera necesario desactivar los microplugins manualmente, esto se puede hacer borrando todos los archivos existentes en el directorio 'cache' de la carpeta del plugin.

IMPORTANTE: Se debe aclarar que los microplugins no producen demora en el sitio tal como se puede pensar inicialmente. Para procesar los mismos se usa un archivo de caché que se puede encontrar en la carpeta 'cache'.

Si se desea comprender más a fondo el funcionamiento de este plugin debe leer la sección de preguntas y respuestas.

== Installation ==

Se instala como cualquier otro plugin de WordPress.

== Frequently Asked Questions ==

= ¿Que relación tienen los microplugins con los plugins de WordPress? =

Los plugins de WordPress son archivos de código ejecutados por el CMS mientras que los microplugins son archivos de código ejecutados por el plugin. Por tanto con ambas opciones se puede lograr el mismo resultado.

= ¿Cuál es el funcionamiento interno del plugin? =

Cuando se crea una entrada de tipo 'microplugin', automáticamente se genera un archivo PHP con el contenido de la misma. Este archivo de código es incluido en cada ejecución del sitio. Cuando la entrada se edita y se guarda, también se actualiza el archivo. Solo las entradas que se encuentran en estado 'publish' son las que cuentan con un archivo ejecutable por lo que si se pasa la entrada a otro estado este archivo será eliminado.

Si se detecta un error fatal en uno de esos archivos este será eliminado automáticamente y la entrada pasará al estado 'pending'.

= ¿Qué usuarios pueden crear microplugins? =

En el momento de activar el plugin los roles que posean la capacidad 'manage_options' serán los que podrán crearlos.

= ¿Hay algún riesgo de seguridad? =

Como el código es introducido por los usuarios va a depender de los mismos. El uso de los microplugins está pensado para los desarrolladores del sitio.

= ¿Como se desactivan manualmente? =

Borrando todo el contenido de la carpeta 'cache' ubicada dentro del plugin.

= ¿Qué significa la opción 'Recompile All'? =

Esta opción volverá a generar todos los archivos de código de los microplugins a partir de sus entradas. Su uso es poco común.

= ¿Producen demora en el sitio? =

No. Una vez que las entradas son publicadas automáticamente se genera un archivo de código PHP con el contenido de la misma y este archivo es el que se ejecuta en el script.

== Screenshots ==

1. screenshot-1.png Crear nuevo microplugin.
2. screenshot-2.png Múltiples estilos en el editor de código.
3. screenshot-3.png Resultado después de publicar la entrada.
4. screenshot-4.png Advertencias en el editor de código.
5. screenshot-5.png Señalamientos de error en el editor de código y microplugin desactivado automáticamente.

== Changelog ==

= 1.1.1, 1.1.2, 1.1.3 =
* Corregidos defectos en la documentación del plugin.

= 1.1.0 =
* Soporte de etiquetas y categorías.
* Ajustes menores.

= 1.0.0 =
* Editor de código enriquecido (Ace Editor).
* Diferentes estilos para el editor de código.
* Advertencias y errores en el editor de código.
* Opción de recompilar todo.