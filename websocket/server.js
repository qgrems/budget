const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const cors = require('cors');
const winston = require('winston');
const amqp = require('amqplib');

const app = express();
const server = http.createServer(app);
const io = socketIo(server, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"],
    allowedHeaders: ["Content-Type", "Authorization"],
  }
});

const logger = winston.createLogger({
  level: 'info',
  format: winston.format.combine(
      winston.format.timestamp(),
      winston.format.printf(({ timestamp, level, message }) => {
        return `${timestamp} ${level}: ${message}`;
      })
  ),
  transports: [
    new winston.transports.Console(),
    new winston.transports.File({ filename: 'server.log' })
  ]
});

app.use(express.json());
app.use(cors());

app.post('/emit', (req, res) => {
  const { userId, event } = req.body;
  logger.info(`Emitting event to user ${userId}: ${JSON.stringify(event)}`);
  io.to(userId).emit('event', event);
  res.sendStatus(200);
});

io.on('connection', (socket) => {
  logger.info('a user connected');

  // Join a room based on userId
  const userId = socket.handshake.auth.userId;
  if (userId) {
    socket.join(userId);
    logger.info(`User ${userId} joined their room`);
  }

  socket.on('disconnect', () => {
    logger.info('user disconnected');
  });
});

// RabbitMQ Consumer Setup
async function setupRabbitMQ() {
  try {
    const connection = await amqp.connect({
      protocol: 'amqp',
      hostname: process.env.RABBITMQ_HOST,
      port: process.env.RABBITMQ_PORT,
      username: process.env.RABBITMQ_USER,
      password: process.env.RABBITMQ_PASS,
      vhost: '/'
    });

    const channel = await connection.createChannel();
    const exchange = 'notification_events';

    // Assert the exchange
    await channel.assertExchange(exchange, 'fanout', { durable: true });

    // Exclusive queue for notifications
    const queue = await channel.assertQueue('', {
      exclusive: true,
      arguments: {
        'x-message-ttl': 30000 // Optional: Auto-delete after 30s inactivity
      }
    });

    channel.bindQueue(queue.queue, exchange, '');

    logger.info('RabbitMQ connected and waiting for messages...');

    // Consume messages from the queue
    channel.consume(queue.queue, (msg) => {
      if (msg !== null) {
        const event = JSON.parse(msg.content.toString());
        const headers = msg.properties.headers;

        logger.info(`Received event from RabbitMQ: ${JSON.stringify(event)}`);

        // Emit the event to the appropriate user
        if (event.userId) {
          const eventType = event.type;
          logger.info(`Emitting ${eventType} to user ${event.userId}`);

          io.to(event.userId).emit(eventType, { // Changed from 'notification' to actual event type
            aggregateId: event.aggregateId,
            userId: event.userId,
            requestId: event.requestId,
            type: eventType, // Keep this for backward compatibility
            ...event // Include all event properties
          });
        }

        // Acknowledge the message
        channel.ack(msg);
      }
    });
  } catch (error) {
    logger.error(`RabbitMQ connection error: ${error.message}`);
  }
}

// Start the WebSocket server
server.listen(process.env.PORT, () => {
  logger.info(`WebSocket server running on port ${process.env.PORT}`);
  setupRabbitMQ();
});
