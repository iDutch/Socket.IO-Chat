var PHPUnserialize = require('php-unserialize');
var memcached = require('memcached');
var io = require('socket.io')();
var cookieParser = require('socket.io-cookie');

var User = require('./src/User');

var Memcached = new memcached('127.0.0.1:11211');

io.set('origins', '*:*');
io.use(cookieParser);

var clients = {};
var session;

io.set('authorization', function (handshakeData, accept) {
    if (handshakeData.headers.cookie) {
        Memcached.get('memcached-' + handshakeData.headers.cookie.PHPSESSID, function (err, data) {
            if (data !== undefined) {
                session = PHPUnserialize.unserializeSession(data);
                if(session.user_id !== undefined){
                    accept(null, true);
                }
                return accept(null, false);
            } else {
                return accept(null, false); 
            }
        });
    }
    return accept(null, false);
});

io.on('connection', function (socket) {
    clients[socket.id] = new User(socket.id, session);
    //console.log(new User(socket.id, session));
    socket.broadcast.emit('chat message', '<i>' + clients[socket.id].nickname + ' joined!</i>');
    io.emit('userlist', clients);
    
    socket.on('chat message', function (msg) {
        this.broadcast.emit('chat message', clients[this.id].nickname + ': ' + msg);
    });
    socket.on('disconnect', function () {
        this.broadcast.emit('chat message', '<i>' + clients[socket.id].nickname + ' left!</i>');
        delete clients[this.id];
        io.emit('userlist', clients);
    });
});
io.listen(3000);


/**
 * Incoming connection
 */


