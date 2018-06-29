var express = require('express');
var io = require('socket.io');
var http = require('http');
var redis = require('redis');
var bodyParser = require('body-parser');
var cookieParser = require('cookie-parser');
var axios = require('axios');

var app = express();
var server = http.createServer(app);
var listener = io.listen(server);
var client = redis.createClient();

app.use(cookieParser('attendance'))
app.use(bodyParser.urlencoded({
    extended: false
}))
app.use(express.static(__dirname + '/views'));
app.set('view engine', 'ejs');
app.set('view options', {
    layout: false
});

app.get('/student', function (req, res) {

    cookieStatus(req, res, '/student');

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

    socket.on('disconnect', function () {
        console.log('Server has disconnected');
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

function clearCookie(res) {
    res.clearCookie('attendance');
}

async function cookieStatus(req, res, page) {
    var cookie = req.cookies['attendance'];
    let cookieOptions = {
        // maxAge: 1000 * 60 * 15, // would expire after 15 minutes
        httpOnly: true, // The cookie only accessible by the web server
        signed: false // Indicates if the cookie should be signed
    }
    var options = {
        redirect_url: redirPage,
        access_domain: "attendance"
    }
    if (cookie) {
        // read cookies
        console.log(cookie)
        // clear cookie
        // clearCookie(res);
        var courses = getUserClasse(req)
    } else {
        var redirPage = 'localhost:8080' + page

        await axios.post('http://localhost/moodle/api/v1/authenticate.php', options)
            .then(function (response) {
                console.log('response: ', response.data)

                if (response.token == null) {
                    console.log('token is null');
                    res.redirect(response.data.login_url);
                } else {
                    console.log('token is: ', response.data.token);
                    // Set cookie
                    res.cookie('attendance', response.data.token, cookieOptions) // cookieOptions is optional
                    console.log('cookie created successfully');
                    cookieStatus(res, req, page)
                }
            }).catch(function (error) {
                console.log('error: ', error);
            });
    }
}
async function getUserClasse(req) {
    var page = page.substring(1);
    var token = req.cookie['attendance'];
    var courses = null;
    await axios.get('http://localhost/moodle/api/v1/courses.php', {
            params: {
                token: token
            }
        })
        .then(function (response) {
            console.log(response.data);
            if (teachers_courses == null) {
                courses = response.student_courses;
            } else {
                courses = response.teacher_courses;
            }
        })
        .catch(function (error) {
            console.log(error);
        });

    return courses;
}


app.listen(8080);