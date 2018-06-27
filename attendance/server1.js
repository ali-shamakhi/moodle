var express = require('express');
var io = require('socket.io');
var http = require('http');
var bodyParser = require('body-parser');
var cache = require('memory-cache');
var redis = require('redis');
var request = require('request');


var app = express();
var server = http.createServer(app);
var listener = io.listen(server);
var client = redis.createClient();

app.use(bodyParser.urlencoded({
    extended: false
}));
app.use(express.static(__dirname + '/views'));
app.set('view engine', 'ejs');
app.set('view options', {
    layout: false
});

app.get('/student', function (req, res) {
    res.render('student', {
        title: 'Your Attendance Page',
    });
});

app.get('/teacher', function (req, res) {
    res.render('teacher', {
        title: 'Student Attendance Page',
    });
});

listener.on('connection', function (socket) {

    console.log('Connection to client established');
    socket.on('login', function () {
        var options = {
            host: url,
            port: 80,
            path: '/resource?id=foo&bar=baz',
            method: 'POST'
        };

        http.request(options, function (res) {
            console.log('STATUS: ' + res.statusCode);
            console.log('HEADERS: ' + JSON.stringify(res.headers));
            res.setEncoding('utf8');
            res.on('data', function (chunk) {
                console.log('BODY: ' + chunk);
            });
        }).end();
    });
    socket.on('disconnect', function () {
        console.log('Server has disconnected');
    });

    socket.on('prof classes', function (data) {
        listener.emit('prof classes', {
            name: data.name,
            students: data.students
        });
    });

    socket.on('login',function(data){
        if(cache.get("token") == null){
            app.redirect_url(checkLogin(data))
        }else{
            listener.emit('login', {
                isLogin: true
            });
        }
    });

    socket.on('chat message', function (data) {

        var name = data.name
        var stIndex = name.indexOf("STUDENT")
        var pIndex = name.indexOf("PROF")


        if (stIndex > -1) {
            console.log(`student id: ${name.substring(7)}`)
            //hash withh hashname: student, key: data.name, value of: data.absence
            client.hset("student", name.substring(7), data.absence, redis.print)
            client.hgetall("student", function (err, replies) {
                console.dir(replies)
            })
            listener.emit('chat message', {
                name: data.name,
                student: data.student,
                absence: data.absence
            });

        } else if (pIndex > -1) {
            console.log(`prof id: ${name.substring(4)}`)
            listener.emit('chat message', {
                name: data.name,
                prof: data.prof,
                absence: data.absence
            });
            client.hset("prof", name.substring(4), data.absence, redis.print)
            client.hgetall("prof", function (err, replies) {
                console.dir(replies)
            })
        }
    });
});

function checkLogin(data) {
    var redir = "localhost:8080/" + data.redir 

    var headers = {
        'User-Agent': 'Super Agent/0.0.1',
        'Content-Type': 'application/x-www-form-urlencoded'
    }

    // Configure the request
    var options = {
        url: 'localhost/moodle',
        method: 'POST',
        headers: headers,
        form: {
            "redirect_url": redir,
            "access_domain": "attendance"
        }
    }

    // Start the request
    request(options, function (error, response, body) {
        if (!error && response.statusCode == 200) {

            if(body.access_token != null){
                cache.set("token",body.access_token)
            }else{
                return body.login_url
            }
        }
    })

}

server.listen(8080);