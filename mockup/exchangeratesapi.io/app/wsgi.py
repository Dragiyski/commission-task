import re, datetime, time, random
from flask import Flask, jsonify, request
from werkzeug.middleware.proxy_fix import ProxyFix

app = Flask(__name__)
app.wsgi_app = ProxyFix(
    app.wsgi_app, x_for=1, x_proto=1, x_host=1, x_prefix=1
)

@app.route('/')
def main():
    return jsonify({
        'request': request.url
    })

@app.route('/convert')
def convert():
    currency_from = request.args.get('from', None)
    if currency_from is None or re.fullmatch(r'[A-Z]{3}', currency_from) is None:
        return make_api_error(201, 'An invalid base currency has been entered.')
    currency_to = request.args.get('to', None)
    if currency_to is None or re.fullmatch(r'[A-Z]{3}', currency_to) is None:
        return make_api_error(201, 'An invalid base currency has been entered.')
    amount = request.args.get('amount', None)
    if amount is None:
        return make_api_error(403, 'No amount has been specified.')
    if re.fullmatch(r'(?:[1-9][0-9]*|0)(?:\.[0-9]*)?', amount) is None:
        return make_api_error(403, 'Invalid amount has been specified.')
    date = request.args.get('date', None)
    if date is not None:
        try:
            date = datetime.date(*(time.strptime(date, r'%Y-%m-%d')[0:3]))
        except ValueError:
            return make_api_error(302, 'An invalid date has been specified.')
    seed = f'{currency_from}-{currency_to}'
    if date is not None:
        seed += f'-{date}'
    rng = random.Random(seed)
    convertion_rate = 0.1 + rng.random() * 0.9 + rng.random()
    timestamp = int((datetime.datetime.now(datetime.timezone.utc) - datetime.datetime(1970, 1, 1, tzinfo=datetime.timezone.utc)).total_seconds())
    result = {
        'success': True,
        'query': {
            'from': currency_from,
            'to': currency_to,
            'amount': amount
        },
        'info': {
            'timestamp': timestamp,
            'rate': convertion_rate
        },
        'historical': date is not None,
        'date': date.strftime(r'%Y-%m-%d') if date is not None else datetime.datetime.now(datetime.timezone.utc).strftime(r'%Y-%m-%d'),
        'result': float(amount) * convertion_rate
    }
    return jsonify(result)
    
    
def make_api_error(code, text):
    response = jsonify({
        'success': False,
        'error': {
            'code': code,
            'info': text
        }
    })
    return response

if __name__ == '__main__':
    app.run(debug=True)
