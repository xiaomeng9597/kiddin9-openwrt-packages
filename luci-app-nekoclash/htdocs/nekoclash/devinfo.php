<?php

$dt=json_decode((shell_exec("ubus call system board")), true);
// MACHINE INFO
$devices=$dt['model'];

// OS TYPE AND KERNEL VERSION
$kernelv=exec("cat /proc/sys/kernel/ostype").' '.exec("cat /proc/sys/kernel/osrelease");
$OSVer=$dt['release']['distribution']." ".$dt['release']['version'];

// MEMORY INFO
$tmpramTotal=exec("cat /proc/meminfo | grep MemTotal | awk '{print $2}'");
$tmpramAvailable=exec("cat /proc/meminfo | grep MemAvailable | awk '{print $2}'");

$ramTotal=number_format(($tmpramTotal/1000),1);
$ramAvailable=number_format(($tmpramAvailable/1000),1);
$ramUsage=number_format((($tmpramTotal-$tmpramAvailable)/1000),1);

// UPTIME
$raw_uptime = exec("cat /proc/uptime | awk '{print $1}'");
$days = floor($raw_uptime / 86400);
$hours = floor(($raw_uptime / 3600) % 24);
$minutes = floor(($raw_uptime / 60) % 60);
$seconds = $raw_uptime % 60;


// CPU FREQUENCY
/*  $cpuFreq = file_get_contents("/sys/devices/system/cpu/cpu0/cpufreq/scaling_cur_freq");
$cpuFreq = round($cpuFreq / 1000, 1);

// CPU TEMPERATURE
$cpuTemp = file_get_contents("/sys/class/thermal/thermal_zone0/temp");
$cpuTemp = round($cpuTemp / 1000, 1);
if ($cpuTemp >= 60) {
    $color = "red";
} elseif ($cpuTemp >= 50) {
    $color = "orange";
} else {
    $color = "white";
}

*/

// CPU LOAD AVERAGE
$cpuLoad = shell_exec("cat /proc/loadavg");
$cpuLoad = explode(' ', $cpuLoad);
$cpuLoadAvg1Min = round($cpuLoad[0], 2);
$cpuLoadAvg5Min = round($cpuLoad[1], 2);
$cpuLoadAvg15Min = round($cpuLoad[2], 2);

// CPU INFORMATION
/* $cpuInfo = shell_exec("lscpu");
$cpuCores = preg_match('/^CPU\(s\):\s+(\d+)/m', $cpuInfo, $matches);
$cpuThreads = preg_match('/^Thread\(s\) per core:\s+(\d+)/m', $cpuInfo, $matches);
$cpuModelName = preg_match('/^Model name:\s+(.+)/m', $cpuInfo, $matches);
$cpuFamily = preg_match('/^CPU family:\s+(.+)/m', $cpuInfo, $matches);
*/
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GitHub Music Player</title>

<style>
     #container {
          text-align: center;
          margin-top: 50px;
    }
    body {
        font-family: Arial, sans-serif;
        background-color: #f0f0f0;
        overflow: hidden; 
    }

    #player {
        width: 320px;
        height: 320px; 
        margin: 50px auto;
        padding: 20px;     
        background: url('/nekoclash/assets/img/3.svg') no-repeat center center;
        background-size: cover;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        align-items: center;
        border-radius: 50%;
        transform-style: preserve-3d; 
        transition: transform 0.5s; 
        position: relative;
        animation: rainbow 5s infinite, rotatePlayer 10s linear infinite;
    }

    #player:hover {
        transform: rotateY(360deg) rotateX(360deg);
    }

    #player h2 {
        margin-top: 0;
    }

    #controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        
    }

    button {
        background-color: #4CAF50;
        border: none;
        color: white;
        padding: 10px 20px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 16px;
        margin: 4px 2px;
        cursor: pointer;
        box-shadow: 0 4px #666;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    button:active {
        transform: translateY(4px);
        box-shadow: 0 2px #444;
    }

    @keyframes rainbow {
        0% {background-color: red;}
        10% {background-color: orange;}
        20% {background-color: yellow;}
        30% {background-color: #4CAF50;} 
        40% {background-color: cyan;}
        50% {background-color: blue;}
        60% {background-color: indigo;}
        70% {background-color: violet;}
        80% {background-color: magenta;}
        90% {background-color: pink;}
        100% {background-color: red;}
    }

    @keyframes rotatePlayer {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @keyframes fall {
        0% {
            transform: translateY(0) translateX(0);
            opacity: 1;
        }
        100% {
            transform: translateY(100vh) translateX(calc(-50% + 50vw));
            opacity: 0;
        }
    }

    .petal {
            position: absolute;
            top: 0;
            width: 20px;
            height: 20px;
            background: pink;
            border-radius: 50%;
            animation: fall linear;
        }

        #hidePlayer, #timeDisplay {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
            background: linear-gradient(90deg, #FF0000, #FF7F00, #FFFF00, #00FF00, #0000FF, #4B0082, #9400D3);
            -webkit-background-clip: text;
            color: transparent;
            transition: background 1s ease;
        }

	.rounded-button {
            border-radius: 30px 15px;
        }
        #tooltip {
            position: absolute;
            background-color: green;
            color: #fff;
            padding: 5px;
            border-radius: 5px;
            display: none;
        }
        #mobile-controls {
            margin-top: 20px;
            transition: opacity 1s ease-in-out;
            opacity: 1;
        }
        #mobile-controls.hidden {
            opacity: 0;
            pointer-events: none; 
        }

        @media (min-width: 768px) {
            #mobile-controls {
                display: none;
            }
        }

        @media (max-width: 767px) {
            #mobile-controls {
                display: block;
            }
        }
    </style>
</head>
<body>
<div id="tooltip"></div>

<script>
    let colors = ['#FF0000', '#FF7F00', '#FFFF00', '#00FF00', '#0000FF', '#4B0082', '#9400D3'];
    let isPlayingAllowed = false; 
    let isLooping = false; 
    let isOrdered = false; 
    let currentSongIndex = 0;
    let songs = [];
    const audioPlayer = document.getElementById('audioPlayer');

    function applyGradient(text, elementId) {
        const element = document.getElementById(elementId);
        element.innerHTML = ''; 
        for (let i = 0; i < text.length; i++) {
            const span = document.createElement('span');
            span.textContent = text[i];
            span.style.color = colors[i % colors.length];
            element.appendChild(span);
        }
        const firstColor = colors.shift();
        colors.push(firstColor);
    }

    function updateTime() {
        const now = new Date();
        const hours = now.getHours();
        const timeString = now.toLocaleTimeString('zh-CN', { hour12: false });
        let ancientTime;

        if (hours >= 23 || hours < 1) {
            ancientTime = '子時';
        } else if (hours >= 1 && hours < 3) {
            ancientTime = '丑時';
        } else if (hours >= 3 && hours < 5) {
            ancientTime = '寅時';
        } else if (hours >= 5 && hours < 7) {
            ancientTime = '卯時';
        } else if (hours >= 7 && hours < 9) {
            ancientTime = '辰時';
        } else if (hours >= 9 && hours < 11) {
            ancientTime = '巳時';
        } else if (hours >= 11 && hours < 13) {
            ancientTime = '午時';
        } else if (hours >= 13 && hours < 15) {
            ancientTime = '未時';
        } else if (hours >= 15 && hours < 17) {
            ancientTime = '申時';
        } else if (hours >= 17 && hours < 19) {
            ancientTime = '酉時';
        } else if (hours >= 19 && hours < 21) {
            ancientTime = '戌時';
        } else {
            ancientTime = '亥時';
        }

        const displayString = `${timeString} (${ancientTime})`;
        applyGradient(displayString, 'timeDisplay');
    }

    applyGradient('Mihomo', 'hidePlayer');
    updateTime();
    setInterval(updateTime, 1000);

    function showTooltip(text) {
        const tooltip = document.getElementById('tooltip');
        tooltip.textContent = text;
        tooltip.style.display = 'block';
        tooltip.style.left = (window.innerWidth - tooltip.offsetWidth - 20) + 'px';
        tooltip.style.top = '10px';
        setTimeout(hideTooltip, 5000);
    }

    function hideTooltip() {
        const tooltip = document.getElementById('tooltip');
        tooltip.style.display = 'none';
    }

    function handlePlayPause() {
        const playButton = document.getElementById('play');
        if (isPlayingAllowed) {
            if (audioPlayer.paused) {
                showTooltip('播放');
                audioPlayer.play();
                playButton.textContent = '暂停'; 
            } else {
                showTooltip('暂停播放');
                audioPlayer.pause();
                playButton.textContent = '播放'; 
            }
        } else {
            showTooltip('播放被禁止');
            audioPlayer.pause(); 
        }
    }

    function handleOrderLoop() {
        if (isPlayingAllowed) {
            const orderLoopButton = document.getElementById('orderLoop');
            if (isOrdered) {
                isOrdered = false;
                isLooping = !isLooping; 
                orderLoopButton.textContent = isLooping ? '循' : ''; 
                showTooltip(isLooping ? '循环播放' : '暂停循环');
            } else {
                isOrdered = true;
                isLooping = false; 
                orderLoopButton.textContent = '顺';
                showTooltip('顺序播放');
            }
        }
    }

    document.addEventListener('keydown', function(event) {
        switch(event.key) {
            case 'ArrowLeft': 
                document.getElementById('prev').click();
                break;
            case 'ArrowRight': 
                document.getElementById('next').click();
                break;
            case ' ': 
                handlePlayPause();
                break;
            case 'ArrowUp': 
                handleOrderLoop();
                break;
            case 'Escape': 
                isPlayingAllowed = !isPlayingAllowed;
                if (!isPlayingAllowed) {
                    audioPlayer.pause(); 
                    audioPlayer.src = ''; 
                    showTooltip('播放已禁用');
                } else {
                    showTooltip('播放已启用');
                    if (songs.length > 0) {
                        loadSong(currentSongIndex);
                    }
                }
                break;
        }
    });

    document.getElementById('play').addEventListener('click', handlePlayPause);
    document.getElementById('next').addEventListener('click', function() {
        if (isPlayingAllowed) {
            currentSongIndex = (currentSongIndex + 1) % songs.length;
            loadSong(currentSongIndex);
            showTooltip('下一首');
        } else {
            showTooltip('播放被禁止');
        }
    });
    document.getElementById('prev').addEventListener('click', function() {
        if (isPlayingAllowed) {
            currentSongIndex = (currentSongIndex - 1 + songs.length) % songs.length;
            loadSong(currentSongIndex);
            showTooltip('上一首');
        } else {
            showTooltip('播放被禁止');
        }
    });
    document.getElementById('orderLoop').addEventListener('click', handleOrderLoop);

    document.getElementById('togglePlay').addEventListener('click', handlePlayPause);
    document.getElementById('toggleEnable').addEventListener('click', function() {
        isPlayingAllowed = !isPlayingAllowed;
        if (!isPlayingAllowed) {
            audioPlayer.pause(); 
            audioPlayer.src = ''; 
            showTooltip('播放已禁用');
        } else {
            showTooltip('播放已启用');
            if (songs.length > 0) {
                loadSong(currentSongIndex);
            }
        }
    });

    function loadSong(index) {
        if (isPlayingAllowed && index >= 0 && index < songs.length) {
            audioPlayer.src = songs[index];
            audioPlayer.play(); 
        } else {
            audioPlayer.pause(); 
        }
    }

    audioPlayer.addEventListener('ended', function() {
        if (isPlayingAllowed) {
            if (isLooping) {
                audioPlayer.currentTime = 0; 
                audioPlayer.play(); 
            } else {
                currentSongIndex = (currentSongIndex + 1) % songs.length;
                loadSong(currentSongIndex);
            }
        }
    });

    function initializePlayer() {
        if (songs.length > 0) {
            loadSong(currentSongIndex);
        }
    }

    fetch('https://raw.githubusercontent.com/Thaolga/Rules/main/Clash/songs.txt')
        .then(response => response.text())
        .then(data => {
            songs = data.split('\n').filter(url => url.trim() !== '');
            initializePlayer();
            console.log(songs);
        })
        .catch(error => console.error('Error fetching songs:', error));

    window.onload = function() {
        audioPlayer.pause(); 
        setTimeout(() => {
            document.getElementById('mobile-controls').classList.add('hidden'); 
        }, 30000);
    };
</script>
</body>
</html>


<?php
date_default_timezone_set('Asia/Shanghai');
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>语音播报系统</title>
</head>
<body>
    <script>
        const city = 'Beijing'; // 替换为您的城市名
        const apiKey = 'fc8bd2637768c286c6f1ed5f1915eb22'; 

        function speakMessage(message) {
            const utterance = new SpeechSynthesisUtterance(message);
            utterance.lang = 'zh-CN';
            speechSynthesis.speak(utterance);
        }

        function getGreeting() {
            const hours = new Date().getHours();
            if (hours >= 5 && hours < 12) return '早上好！';
            if (hours >= 12 && hours < 18) return '下午好！';
            if (hours >= 18 && hours < 22) return '晚上好！';
            return '夜深了，注意休息！';
        }

        function speakCurrentTime() {
            const now = new Date();
            const hours = now.getHours();
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const seconds = now.getSeconds().toString().padStart(2, '0');
            const currentTime = `${hours}点${minutes}分${seconds}秒`;

            const timeOfDay = (hours >= 5 && hours < 8) ? '清晨'
                              : (hours >= 8 && hours < 11) ? '早上'
                              : (hours >= 11 && hours < 13) ? '中午'
                              : (hours >= 13 && hours < 18) ? '下午'
                              : (hours >= 18 && hours < 20) ? '傍晚'
                              : (hours >= 20 && hours < 24) ? '晚上'
                              : '凌晨';

            speakMessage(`${getGreeting()} 现在是北京时间: ${timeOfDay}${currentTime}`);
        }

        function updateTime() {
            const now = new Date();
            const hours = now.getHours();
            const timeOfDay = (hours >= 5 && hours < 8) ? '清晨'
                              : (hours >= 8 && hours < 11) ? '早上'
                              : (hours >= 11 && hours < 13) ? '中午'
                              : (hours >= 13 && hours < 18) ? '下午'
                              : (hours >= 18 && hours < 20) ? '傍晚'
                              : (hours >= 20 && hours < 24) ? '晚上'
                              : '凌晨';

            if (now.getMinutes() === 0 && now.getSeconds() === 0) {
                speakMessage(`整点播报，现在是北京时间 ${timeOfDay} ${hours}点`);
            }
        }

        const websites = [
            'https://www.youtube.com/',
            'https://www.google.com/',
            'https://www.facebook.com/',
            'https://www.twitter.com/',
            'https://www.github.com/'
        ];

        function getWebsiteStatusMessage(url, status) {
            const statusMessages = {
                'https://www.youtube.com/': status ? 'YouTube 网站访问正常。' : '无法访问 YouTube 网站，请检查网络连接。',
                'https://www.google.com/': status ? 'Google 网站访问正常。' : '无法访问 Google 网站，请检查网络连接。',
                'https://www.facebook.com/': status ? 'Facebook 网站访问正常。' : '无法访问 Facebook 网站，请检查网络连接。',
                'https://www.twitter.com/': status ? 'Twitter 网站访问正常。' : '无法访问 Twitter 网站，请检查网络连接。',
                'https://www.github.com/': status ? 'GitHub 网站访问正常。' : '无法访问 GitHub 网站，请检查网络连接。',
            };

            return statusMessages[url] || (status ? `${url} 网站访问正常。` : `无法访问 ${url} 网站，请检查网络连接。`);
        }

        function checkWebsiteAccess(urls) {
            const statusMessages = [];
            let requestsCompleted = 0;

            urls.forEach(url => {
                fetch(url, { mode: 'no-cors' })
                    .then(response => {
                        const isAccessible = response.type === 'opaque';
                        statusMessages.push(getWebsiteStatusMessage(url, isAccessible));
                        
                        if (!isAccessible && url === 'https://www.youtube.com/') {
                            speakMessage('无法访问 YouTube 网站，请检查网络连接。');
                        }
                    })
                    .catch(() => {
                        statusMessages.push(getWebsiteStatusMessage(url, false));
                        
                        if (url === 'https://www.youtube.com/') {
                            speakMessage('无法访问 YouTube 网站，请检查网络连接。');
                        }
                    })
                    .finally(() => {
                        requestsCompleted++;
                        if (requestsCompleted === urls.length) {
                            speakMessage(statusMessages.join(' '));
                        }
                    });
            });
        }

        function getRandomPoem() {
            const poems = [
                '红豆生南国，春来发几枝。', '独在异乡为异客，每逢佳节倍思亲。',
                '海上生明月，天涯共此时。', '但愿人长久，千里共婵娟。',
                '江南好，风景旧曾谙。', '君不见黄河之水天上来，奔流到海不复回。',
                '露从今夜白，月是故乡明。', '自古逢秋悲寂寥，我言秋日胜春朝。',
                '两岸猿声啼不住，轻舟已过万重山。', '一去二三里，烟村四五家。',
                '问君何为别，心逐青云行。', '风急天高猿啸哀，渚清沙白鸟飞回。',
                '锦城虽云乐，不如早还家。', '白下驿穷冬望，红楼隔雨弄晴寒。',
                '夜泊牛渚怀古，牛渚西江夜。', '空山新雨后，天气晚来秋。',
                '山中相送罢，日暮掩柴扉。', '寒蝉凄切，对长亭晚，骤雨初歇。',
                '湖上初晴后雨，水面晕开清晖。', '孤舟蓑笠翁，独钓寒江雪。',
                '黄河远上白云间，一片孤城万仞山。', '松下问童子，言师采药去。',
                '白云深处有人家，黄鹤楼中吹玉笛。', '枯藤老树昏鸦，小桥流水人家。',
                '寒山转苍翠，秋水共长天一色。', '年年岁岁花相似，岁岁年年人不同。',
                '锦江春色来天地，玉垒浮云变古今。', '天街小雨润如酥，草色遥看近却无。',
                '长江绕郭知鱼美，苏堤春晓胜地宜。'
            ];
            return poems[Math.floor(Math.random() * poems.length)];
        }

        function speakRandomPoem() {
            const poem = getRandomPoem();
            speakMessage(`${poem}`);
        }

        function speakWeather(weather) {
            const descriptions = {
                "clear sky": "晴天", "few clouds": "少量云", "scattered clouds": "多云",
                "broken clouds": "多云", "shower rain": "阵雨", "rain": "雨", 
                "light rain": "小雨", "moderate rain": "中雨", "heavy rain": "大雨",
                "very heavy rain": "特大暴雨", "extreme rain": "极端降雨",
                "thunderstorm": "雷暴", "thunderstorm with light rain": "雷阵雨", "thunderstorm with heavy rain": "强雷雨",
                "snow": "雪", "light snow": "小雪", "moderate snow": "中雪", "heavy snow": "大雪",
                "very heavy snow": "特大暴雪", "extreme snow": "极端降雪",
                "sleet": "雨夹雪", "freezing rain": "冻雨", "mist": "薄雾",
                "fog": "雾", "haze": "霾", "sand": "沙尘", "dust": "扬尘", "squall": "阵风",
                "tornado": "龙卷风", "ash": "火山灰", "drizzle": "毛毛雨",
                "overcast": "阴天", "partly cloudy": "局部多云", "cloudy": "多云",
                "tropical storm": "热带风暴", "hurricane": "飓风", "cold": "寒冷", 
                "hot": "炎热", "windy": "大风", "breezy": "微风", "blizzard": "暴风雪"
            };

            const weatherDescription = descriptions[weather.weather[0].description.toLowerCase()] || weather.weather[0].description;
            const temperature = weather.main.temp;
            const tempMax = weather.main.temp_max;
            const tempMin = weather.main.temp_min;
            const humidity = weather.main.humidity;
            const windSpeed = weather.wind.speed;
            const visibility = weather.visibility / 1000;

            let message = `以下是今天${city}的天气预报：当前气温为${temperature}摄氏度，${weatherDescription}。` +
                          `预计今天的最高气温为${tempMax}摄氏度，今晚的最低气温为${tempMin}摄氏度。`;

            if (weather.rain && weather.rain['1h']) {
                var rainProbability = weather.rain['1h'];
                message += ` 接下来一小时有${rainProbability * 100}%的降雨概率。`;
            } else if (weather.rain && weather.rain['3h']) {
                var rainProbability = weather.rain['3h'];
                message += ` 接下来三小时有${rainProbability * 100}%的降雨概率。`;
            } else {
                message += ' 今天降雨概率较低。';
            }

            message += ` 西南风速为每小时${windSpeed}米。` +
                       ` 湿度为${humidity}%。`;

            if (weatherDescription.includes('晴') || weatherDescription.includes('阳光明媚')) {
                message += ` 紫外线指数适中，如果外出，请记得涂防晒霜。`;
            } else if (weatherDescription.includes('雨') || weatherDescription.includes('阵雨') || weatherDescription.includes('雷暴')) {
                message += ` 建议您外出时携带雨伞。`;
            }

            message += ` 能见度为${visibility}公里。` +
                       `请注意安全，保持好心情，祝您有美好的一天！`;

            speakMessage(message);
        }

        function fetchWeather() {
            const apiUrl = `https://api.openweathermap.org/data/2.5/weather?q=${city}&appid=${apiKey}&units=metric&lang=zh_cn`; 
            fetch(apiUrl)
                .then(response => response.ok ? response.json() : Promise.reject('网络响应不正常'))
                .then(data => {
                    if (data.weather && data.main) {
                        speakWeather(data);
                    } else {
                        console.error('无法获取天气数据');
                    }
                })
                .catch(error => console.error('获取天气数据时出错:', error));
        }

        window.onload = function() {
            speakMessage('欢迎使用语音播报系统！');
            checkWebsiteAccess(websites);
            speakCurrentTime();
            fetchWeather();
            speakRandomPoem(); 
            setInterval(updateTime, 1000);
        };
    </script>
</body>
</html>