# Setup Domain Names for SSO Testing

## Add to /etc/hosts

Add these lines to your `/etc/hosts` file:

```bash
sudo nano /etc/hosts
```

Add these lines:
```
127.0.0.1 sso.local
127.0.0.1 tenant1.local  
127.0.0.1 tenant2.local
```

## Update Environment Files

We need to update the environment files to use these domains instead of localhost:port.