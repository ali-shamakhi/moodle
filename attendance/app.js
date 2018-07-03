var express = require('express');
var io = require('socket.io');
var http = require('http');
var redis = require('redis');
var bodyParser = require('body-parser');
var cookieParser = require('cookie-parser');
var browser = require('browser-cookies');
var axios = require('axios');

// server
var app = express();
var server = http.createServer(app);

//socket.io
var listener = io.listen(server);

// redis client
var client = redis.createClient();

app.use(cookieParser())
app.use(bodyParser.urlencoded({
    extended: false
}))
app.use(express.static(__dirname + '/views'));
app.set('view engine', 'ejs');
app.set('view options', {
    layout: false
});
app.get('/test1', function (req, res) {
    // res.cookie('attendance', 1234);
    // console.log(req.cookies)
    res.clearCookie('attendance')
    // console.log(req.cookies)
    var token = req.cookies.attendance;
    console.log(token)
    if (token === undefined) {
        console.log("token is undifined")
        res.send('token is undifined');
    } else {
        res.send(req.cookies.attendance);
    }
});
app.get('/test2', function (req, res) {
    var header = req.headers;
    // console.log(header);

    var token = header.token
    if (token !== undefined) {
        console.log('token: ', token)
        res.send(JSON.stringify({
            token: token,
        }));
    } else {
        console.log('token in undefined')
        res.send('token in undefined');
    }
});
app.get('/test3', function (req, res) {
    res.render('teacher', {
        title: 'test Attendance Page',
    });
});
app.get('/student', function (req, res) {

    var coursesss = [];

    // res.cookie('attendance', 1234); // cookieOptions is optional
    // res.clearCookie('attendance');
    var token_header = req.headers.token

    if (token_header !== undefined) {
        res.cookie('attendance', token_header)
        retriveClasses(token, function (res, courses) {
            coursesss = courses;
            console.dir(courses)
            getUserName(function (res, name) {
                console.log('name: ', name)
                res.render('student', {
                    title: 'Student Attendance Page',
                    courses: coursesss,
                    username: name
                });
            });
        });
    } else {
        var token = req.cookies.attendance;
        if (token === undefined) {
            getAuthenticateStatus('/student', function (res, url) {
                console.log(url)
                res.redirect(url);
            }, res);
        } else {
            retriveClasses(token, function (res, courses) {
                coursesss = courses;
                console.dir(courses)
                getUserName(function (res, name) {
                    console.log('name: ', name)
                    res.render('student', {
                        title: 'Student Attendance Page',
                        courses: coursesss,
                        username: name
                    });
                });
            });
        }
    }
});

app.get('/teacher', function (req, res) {
    var coursesss = [];

    // res.cookie('attendance', 1234); // cookieOptions is optional
    // res.clearCookie('attendance');
    var token_header = req.headers.token

    if (token_header !== undefined) {
        res.cookie('attendance', token_header)
        retriveClasses(token, function (res, courses) {
            coursesss = courses;
            console.dir(courses)
            getUserName(function (res, name) {
                console.log('name: ', name)
                res.render('teacher', {
                    title: 'Teacher Attendance Page',
                    courses: coursesss,
                    username: name
                });
            });
        });
    } else {
        var token = req.cookies.attendance;
        if (token === undefined) {
            getAuthenticateStatus('/student', function (res, url) {
                console.log(url)
                res.redirect(url);
            }, res);
        } else {
            retriveClasses(token, function (res, courses) {
                coursesss = courses;
                console.dir(courses)
                getUserName(function (res, name) {
                    console.log('name: ', name)
                    res.render('teacher', {
                        title: 'Teacher Attendance Page',
                        courses: coursesss,
                        username: name
                    });
                });
            });
        }
    }
});
app.get('/mediator', function (req, res) {

    // var token = req.cookies.attendance;
    // var url = 'http://localhost/moodle/api/v1/course/details.php';
    var url = 'https://jsonplaceholder.typicode.com/users';

    // if (token === undefined) {

    // } else {
    axios.get(url, {
        headers: {
            authorization: token
        },
        params: {
            course_id: course_id
        }
    }).then(function (response) {
        return response.data
    }).catch(function (error) {

    })
    // }
});

listener.on('connection', function (socket) {

    console.log('Connection to client established');

    socket.on('prof classes', function (data) {
        listener.emit('prof classes', {
            name: data.name,
            student: data.student
        });
    });

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

async function getAuthenticateStatus(page, callback, pass) {
    var redirPage = 'http://localhost:8080' + page
    var options = {
        redirect_url: redirPage,
        access_domain: "attendance"
    }
    await axios.post('http://localhost/moodle/api/v1/authenticate.php', options)
        .then(function (response) {
            // console.log('response: ', response.data)
            // console.log('token: \'', response.data.access_token,'\'')
            // var result = null;
            // var token = response.data.access_token;
            var login_url = response.data.login_url;

            // if (token == null) {
            //     console.log('token: ', token);
            //     console.log('login url: ', login_url)
            //     result = login_url
            // } else {
            //     console.log('token is: ', token);
            //     // Set cookie
            //     var cookieOptions = {}
            //     res.cookie('attendance', token, cookieOptions) // cookieOptions is optional
            //     browser.set('attendance', token)
            //     console.log('cookie created successfully');
            //     result = redirPage
            // }
            callback(pass, login_url)
        }).catch(function (error) {
            console.log('____________________________________________________________ERROR')
            console.log('error: ', error);
            callback(pass, '/error')
        });
}
async function retriveClasses(token, callback) {
    var courses = [];
    await axios.get('http://localhost/moodle/api/v1/courses.php', {
            params: {
                token: token
            }
        })
        .then(function (response) {
            console.log(response.data);
            var teachers_courses = response.data.teacher_courses
            var student_courses = response.data.student_courses
            if (teachers_courses == null) {
                courses = response.student_courses;
            } else {
                courses = response.teacher_courses;
            }
        })
        .catch(function (error) {
            console.log(error);
        });

    callback(courses);
}
async function getUserName(callback) {
    await axios.get('http://localhost/moodle/api/v1/user/details.php', {})
        .then(function (response) {
            console.log(response.data)
            callback(response.data.full_name);
        });
}

function getToken() {
    var token = browser.get('attendance');
    console.log('getToken: ', token);
    return token
}

app.listen(8080);