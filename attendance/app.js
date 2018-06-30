var express = require('express');
var io = require('socket.io');
var http = require('http');
var redis = require('redis');
var bodyParser = require('body-parser');
var cookieParser = require('cookie-parser');
var browser = require('browser-cookies');
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

    var courses = null;
    var username = null;
    var token = req.cookies['attendance'];
    if (token) {
        courses = retriveClasses(token)
        username = getUserName();
        console.log(coourses)
        console.log(username)
    } else {
        var url = getAuthenticateStatus('/student');
        res.redirect(url);
    }


    res.render('student', {
        title: 'Student Attendance Page',
        courses: courses,
        username: username
    });
});

app.get('/teacher', function (req, res) {
    var courses = null;
    var username = null;
    var token = req.cookies['attendance'];
    if (token) {
        courses = retriveClasses(token)
        username = getUserName();
        console.log(coourses)
        console.log(username)
    } else {
        var url = getAuthenticateStatus('/teacher');
        res.redirect(url);
    }

    res.render('teacher', {
        title: 'Student Attendance Page',
        courses: courses,
        username: username,
        token: token
    });
});

listener.on('connection', function (socket) {

    console.log('Connection to client established');

    socket.on('disconnect', function () {
        console.log('Server has disconnected');
    });

    socket.on('attendance', function (data) {

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
            listener.emit('attendance', {
                name: data.name,
                student: data.student,
                absence: data.absence
            });

        } else if (pIndex > -1) {
            console.log(`prof id: ${name.substring(4)}`)
            listener.emit('attendance', {
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

async function getAuthenticateStatus(page) {
    var redirPage = 'localhost:8080' + page
    var options = {
        redirect_url: redirPage,
        access_domain: "attendance"
    }
    await axios.post('http://localhost/moodle/api/v1/authenticate.php', options)
        .then(function (response) {
            // console.log('response: ', response.data)

            if (response.data.token == null) {
                console.log('token is null');
                return response.data.login_url
            } else {
                console.log('token is: ', response.data.token);
                // Set cookie
                // res.cookie('attendance', response.data.token, cookieOptions) // cookieOptions is optional
                browser.set('attendance', response.data.token)
                console.log('cookie created successfully');
                return redirPage
            }
        }).catch(function (error) {
            console.log('error: ', error);
        });
}
async function retriveClasses(token) {
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
async function getUserName() {
    await axios.get('http://localhost/moodle/api/v1/user/details.php', {})
        .then(function (response) {
            console.log(response.data)
            return response.data.full_name
        });
}

function getCourseDetail(token, course_id) {
    var url = 'http://localhost/moodle/api/v1/course/details.php';
    axios.get(url, {
        headers: {
            'authorization': token
        },
        params: {
            course_id: course_id
        }
    }).then(function (response) {
        return response.data
    })
}

function getToken() {
    var token = browser.get('attendance');
    console.log('getToken: ', token);
    return token
}

app.listen(8080);