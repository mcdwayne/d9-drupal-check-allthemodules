# Info Messages



## English

The Info Message module is intended to allow Drupal users and developers to process informational messages with a blue background, so simply call the drupal_set_message function and pass the second parameter (type) to the string "info".enter (type) to the string "info".


## Example
    \Drupal::messenger()->addMessage('This is an info message!', 'info', FALSE);
    



## Português

O modulo infomessage tem a intenção de permitir aos usuários e desenvolvedores Drupal a renderização de mensagens informativas com fundo azul claro, para isso basta chamar a função drupal_set_message e passar como segundo paramentro (tipo) a string "info".

## Exemplo:
    \Drupal::messenger()->addMessage('Esta é uma mensagem informativa!', 'info', FALSE);
