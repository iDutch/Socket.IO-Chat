function User(socket_id, sessionData) {
    this.socket_id = socket_id;
    this.nickname = sessionData.nickname;
    this.user_id = sessionData.user_id;
}

User.Prototype = {

    getUserId: function() {
        return this.user_id;
    },

    getNickname: function() {
        return this.nickname;
    },

    getSocketId: function() {
        return this.socket_id;
    }

}

module.exports = User;
