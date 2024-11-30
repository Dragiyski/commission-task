FROM python:latest

RUN apt-get update;\
    apt-get install -y --no-install-recommends \
    ca-certificates \
    curl \
    wget \
    git \
    bzip2 \
    libbz2-dev \
    xz-utils \
    unzip \
    zlib1g-dev

ENV PIP_ROOT_USER_ACTION=ignore
RUN pip install gunicorn flask

VOLUME /usr/src/app
WORKDIR /usr/src/app
EXPOSE 8000

CMD ["python", "-m", "gunicorn", "-b", "0.0.0.0:8000", "wsgi:app"]
