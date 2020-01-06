# FSN
File Sharing Network [Available Languages : FA]

# How to install
<b>1.</b> Upload the source into your host
Like this directory : <code>/public_html/fsn</code><br />

<b>2.</b> Now we edit <code>config.php</code><br />
The file in text editor, goes like this:<br />

<code>$config = array(</code><br />
<code>  'token' => '123456789:MSX15Awsome',</code><br />
<code>  'bot_username' => 'FSNbot',</code><br />
<code>  'db' => 'rfiles.sqlite'</code><br />
<code>  );</code><br /><br />

you should change '123456...' and 'FSNbot' to your own bot's token and username.<br />
Now you're done with your bot's config file.<br />

<b>3.</b> Now you should setwebhook, <br />
You can setwebhook with clicking this kind of link:<br />
<code>https://api.telegram.org/bot[TOKEN]/setwebook?url=[LINK2BOT.PHP]</code><br />
but you must change:<br />
[TOKEN] => to your token,<br />
[LINK2BOT.PHP] => to your bot.php address<i>(address should start with https://</i>)<br /><br />

For Example :<br />
I run this link in my browser :<br />
<code>https://api.telegram.org/bot123456789:MSX15Awesome/setwebook?url=https://msxtm.ir/fsn/bot.php</code>
