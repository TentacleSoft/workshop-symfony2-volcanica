'use strict';

var gameId = 1,
    game = {},
    pollingRate = 1000,
    section = 'lobby';

$(document).ready(function () {
    setInterval(function () {
        if (typeof globals != 'undefined') {
            if (section == 'lobby') {
                $.getJSON('/lobby/', function (data) {
                    updateLobbyInfo(data);
                });
                $.getJSON('/users/' + globals.user.id + '/games', function (data) {
                    updateGamesInfo(data);
                });
            } else if (section == 'game') {
                $.getJSON('/games/' + gameId, function (data) {
                    updateGameInfo(data);
                });
            }
        }
    }, pollingRate);

    $('#title').find('a').click(function (event) {
        event.preventDefault();

        changeSection('lobby');
    });

    $('.board-cell.enabled').click(function () {
        if (game.activePlayer != globals.user.id) {
            return;
        }

        var row = $(this).data('row'),
            col = $(this).data('col');
        $.post('/games/' + gameId, {row: row, col: col}, function (data) {
            updateGameInfo(data);
        });
        console.log('Click (' + row + ', ' + col + ')');
    });

    $('#form-chat').submit(function () {
        var $message = $('#message'),
            message = $message.val();

        if (message != '') {
            var url;

            $message.val('');

            if (section == 'lobby') {
                url = '/lobby/chat';
            } else if (section == 'game') {
                url = '/games/' + gameId + '/chat';
            }

            $.post(url, {message: message}, function (data) {
                updateChatInfo(data.chat);
            });
        }

        return false;
    });

    $('#game-list').find('a').on('click', function (event) {
        event.preventDefault();

        $.getJSON($(this).attr('href'), function (data) {
            updateGameInfo(data);
            changeSection('game');
        });

        return false;
    });

    $('#user-list').find('a').on('click', function (event) {
        event.preventDefault();

        $.post($(this).attr('href'), function (data) {
            updateGameInfo(data);
            changeSection('game');
        });

        return false;
    });
});

function updateLobbyInfo(data) {
    updateChatInfo(data.chat);
    updateUsersInfo(data.users);
}

function updateGameInfo(data) {
    gameId = data.id;

    updateChatInfo(data.chat);

    drawBoard(data.board);

    var scoreboard = $('#scoreboard');
    scoreboard.find('div[data-player="0"] .score').text(data.scores[0]);
    scoreboard.find('div[data-player="1"] .score').text(data.scores[1]);

    scoreboard.find('div[data-player="0"] .username').text(data.players[0].username);
    scoreboard.find('div[data-player="1"] .username').text(data.players[1].username);

    var turn = $('#turn');
    if (data.activePlayer == globals.user.id) {
        turn.text('Your turn');
        if (data.players[0].id == globals.user.id) {
            turn.addClass('player0');
        } else {
            turn.addClass('player1');
        }
    } else {
        if (data.players[0].id == data.activePlayer) {
            turn.text(data.players[0].name + '\'s turn');
        } else {
            turn.text(data.players[1].name + '\'s turn');
        }

        turn.removeClass('player0').removeClass('player1');
    }

    game = data;
}

function updateChatInfo(chatData) {
    var chat = $('#chat').html('');

    for (var chatLineKey in chatData) {
        var chatLine = chatData[chatLineKey],
            line = $('<p>').text(chatLine.message);

        switch (chatLine.from) {
            case -1:
                line.addClass('info');
                break;
            case -2:
                line.addClass('error');
                break;
            default:
                line.prepend($('<span>').text(chatLine.from).addClass('username'));
        }

        chat.append(line);
    }

    chat.scrollTop(chat[0].scrollHeight);
}

function updateUsersInfo(usersData) {
    var userList = $('#user-list').html('');

    for (var userKey in usersData) {
        var user = usersData[userKey];

        if (user.id != globals.user.id) {
            var userElement = $('<li>').text(user.username + ' (' + user.games.won.length + '-' + user.games.lost.length + ')'),
                link = $('<a>').attr('href', '/games/' + '?players=' + globals.user.id + ',' + user.id).text('New game');
            userList.append(link.click(onUserClick).add(userElement));
        }
    }
}

function updateGamesInfo(gamesData) {
    var gameList = $('#game-list').html('');

    for (var gameKey in gamesData) {
        var game = gamesData[gameKey],
            player1 = game.players[0].username,
            player2 = game.players[1].username,
            score1 = game.scores[0],
            score2 = game.scores[1],
            gameElement = $('<li>').text(player1 + ' ' + score1 + ' - ' + score2 + ' ' + player2),
            link = $('<a>').attr('href', '/games/' + game.id);
        gameList.append(link.append(gameElement).click(onGameClick));
    }
}

function drawBoard(board) {
    var cells = $('.board-cell');

    for (var pos = 0; pos < cells.length; pos++) {
        var cell = $(cells[pos]),
            cellValue = board[cell.data('row')][cell.data('col')];

        if (cell.hasClass('enabled')) {
            switch (cellValue) {
                case 'M0':
                    cell.removeClass('enabled').addClass('mine').attr('data-player', 0);
                    break;
                case 'M1':
                    cell.removeClass('enabled').addClass('mine').attr('data-player', 1);
                    break;
                case '':
                    break;
                case 0:
                    cell.removeClass('enabled').addClass('open');
                    break;
                default: // if it's a number
                    cell.removeClass('enabled').addClass('open');
                    cell.attr('data-number', cellValue);
                    cell.html(cellValue);
            }
        }
    }
}

function clearBoard() {
    $('.board-cell').removeClass().addClass('board-cell').addClass('enabled').html('');
}

function changeSection(newSection) {
    section = newSection;
    clearBoard();
    $('#chat').html('');
    $('.section').hide();
    $('.section-' + newSection).show();
}

var onGameClick = function (event) {
    event.preventDefault();

    $.getJSON($(this).attr('href'), function (data) {
        updateGameInfo(data);
        changeSection('game');
    });
};

var onUserClick = function (event) {
    event.preventDefault();

    $.post($(this).attr('href'), function (data) {
        updateGameInfo(data);
        changeSection('game');
    });
};
