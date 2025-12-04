const Redis = require("ioredis");
const redis = new Redis({
    host: process.env.REDIS_HOST || "redis",
    port: process.env.REDIS_PORT || 6379
});

const io = require("socket.io")(6001, {
    cors: {
        origin: "*"
    }
});

console.log("Socket.IO server started on :6001");

io.on("connection", (socket) => {
    console.log("Client connected:", socket.id);
});

// подписываемся на ВСЕ события
redis.psubscribe("*", (err, count) => {
    console.log("Subscribed to Redis");
});

redis.on("pmessage", (pattern, channel, message) => {
    try {
        const data = JSON.parse(message);

        const eventName = data.event.replace(/\\/g, ".");

        console.log("Redis event:", channel, eventName);

        io.emit(
            `${channel}:${eventName}`,
            data.data
        );

    } catch (e) {
        console.error("Error parsing message", e);
    }
});
