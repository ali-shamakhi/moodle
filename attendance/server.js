var express = require('express');
var io = require('socket.io');
var http = require('http');
var bodyParser = require('body-parser');
var cache = require('memory-cache');
var redis = require('redis');

var app = express();
var server = http.createServer(app);
var listener = io.listen(server);
var client = redis.createClient();

app.use(bodyParser.urlencoded({extended: false}));
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

app.get('/prof', function (req, res) {
    res.render('prof', {
        title: 'Student Attendance Page',
    });
});

listener.on('connection', function (socket) {
    
    console.log('Connection to client established');
    
    socket.on('disconnect', function () {
        console.log('Server has disconnected');
    });
    
    socket.emit('getCourses')
    socket.on('chat message', function(data){

        var name = data.name
        var stIndex = name.indexOf("STUDENT")
        var pIndex = name.indexOf("PROF")

        
        if(stIndex > -1){
            // console.log(`student id: ${name.substring(7)}`)
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
            
        }else if(pIndex > -1){
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

server.listen(8080);