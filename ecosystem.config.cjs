module.exports = {
  apps: [{
    name: 'church-center-bot',
    cwd: './apps/whatsapp-worker',
    script: 'chiwa.js',
    interpreter: 'node',
    instances: 1,
    exec_mode: 'fork',
    autorestart: true,
    max_memory_restart: '300M',
    restart_delay: 5000,
    time: true,
    env: {
      NODE_ENV: 'production',
      WORKER_HOST: '127.0.0.1',
      WORKER_PORT: '31181'
    }
  }]
};
